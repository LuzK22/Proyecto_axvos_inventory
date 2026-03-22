<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Assignment;
use App\Models\AxiConversation;
use App\Models\AxiMessage;
use App\Models\Collaborator;
use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class AxiController extends Controller
{
    public function hub(Request $request)
    {
        $user = Auth::user();
        $conversationId = $request->get('conversation');

        $conversations = AxiConversation::where('user_id', $user->id)
            ->with('lastMessage')
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();

        $activeConversation = null;
        $messages = collect();

        if ($conversationId) {
            $activeConversation = AxiConversation::where('user_id', $user->id)
                ->with('messages')
                ->find($conversationId);
            if ($activeConversation) {
                $messages = $activeConversation->messages;
            }
        }

        return view('ai.hub', compact('conversations', 'activeConversation', 'messages'));
    }

    public function newConversation()
    {
        $conv = AxiConversation::create([
            'user_id' => Auth::id(),
            'title'   => 'Nueva conversación',
        ]);
        return redirect()->route('ai.hub', ['conversation' => $conv->id]);
    }

    public function deleteConversation(AxiConversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) abort(403);
        $conversation->delete();
        return redirect()->route('ai.hub');
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message'         => 'required|string|max:2000',
            'conversation_id' => 'nullable|exists:axi_conversations,id',
        ]);

        $user    = Auth::user();
        $message = trim($request->message);

        if ($request->filled('conversation_id')) {
            $conv = AxiConversation::where('user_id', $user->id)->findOrFail($request->conversation_id);
        } else {
            $title = strlen($message) > 50 ? substr($message, 0, 47) . '...' : $message;
            $conv  = AxiConversation::create(['user_id' => $user->id, 'title' => $title]);
        }

        AxiMessage::create(['conversation_id' => $conv->id, 'role' => 'user', 'content' => $message]);

        $history = $conv->messages()->orderBy('created_at')->get()
            ->map(fn($m) => ['role' => $m->role, 'content' => $m->content])
            ->toArray();

        $context = $this->buildContext($message);
        $apiKey  = config('services.anthropic.key');
        $reply   = null;

        if ($apiKey) {
            try {
                $response = Http::timeout(30)
                    ->withHeaders([
                        'x-api-key'         => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'content-type'      => 'application/json',
                    ])
                    ->post('https://api.anthropic.com/v1/messages', [
                        'model'      => 'claude-haiku-4-5-20251001',
                        'max_tokens' => 1024,
                        'system'     => $this->systemPrompt($context),
                        'messages'   => $history,
                    ]);

                if ($response->successful()) {
                    $reply = $response->json('content.0.text') ?? 'Sin respuesta del modelo.';
                } else {
                    $reply = 'Error al conectar con el servicio de IA (HTTP ' . $response->status() . ').';
                }
            } catch (\Exception $e) {
                $reply = 'Error de conexión con IA: ' . $e->getMessage();
            }
        } else {
            $reply = $this->demoReply($message, $context);
        }

        $assistantMsg = AxiMessage::create([
            'conversation_id' => $conv->id,
            'role'            => 'assistant',
            'content'         => $reply,
        ]);

        $conv->touch();

        return response()->json([
            'conversation_id' => $conv->id,
            'reply'           => $reply,
            'message_id'      => $assistantMsg->id,
        ]);
    }

    public function exportConversation(AxiConversation $conversation)
    {
        if ($conversation->user_id !== Auth::id()) abort(403);
        $conversation->load('messages');

        $text  = "=== Conversación AXI: {$conversation->title} ===\n";
        $text .= "Fecha: " . $conversation->created_at->format('d/m/Y H:i') . "\n\n";
        foreach ($conversation->messages as $msg) {
            $who   = $msg->role === 'user' ? 'Tú' : 'AXI';
            $text .= "[{$who}] {$msg->created_at->format('H:i')}\n{$msg->content}\n\n";
        }

        return response($text, 200, [
            'Content-Type'        => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="axi_chat_' . $conversation->id . '.txt"',
        ]);
    }

    private function buildContext(string $message): array
    {
        $ctx = [
            'total_ti'             => Asset::whereHas('type', fn($q) => $q->where('category', 'TI'))->count(),
            'total_otro'           => Asset::whereHas('type', fn($q) => $q->where('category', 'OTRO'))->count(),
            'asignaciones_activas' => Assignment::where('status', 'activa')->count(),
            'prestamos_activos'    => Loan::where('status', 'activo')->count(),
            'prestamos_vencidos'   => Loan::where('status', 'vencido')->count(),
            'colaboradores'        => Collaborator::where('active', true)->count(),
        ];

        $lower = strtolower($message);

        if (str_contains($lower, 'disponible')) {
            $ctx['disponibles_ti'] = Asset::whereHas('type', fn($q) => $q->where('category','TI'))
                ->whereHas('status', fn($q) => $q->where('name','like','%Disponible%'))->count();
        }
        if (str_contains($lower, 'colaborador') || str_contains($lower, 'usuario') || str_contains($lower, 'tiene')) {
            $ctx['top_colaboradores'] = Collaborator::withCount('assignments')
                ->orderByDesc('assignments_count')->limit(5)->get()
                ->map(fn($c) => "{$c->full_name} ({$c->assignments_count} asignaciones)")->implode(', ');
        }

        return $ctx;
    }

    private function systemPrompt(array $context): string
    {
        $ctx = json_encode($context, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        return <<<PROMPT
Eres AXI, el asistente inteligente de AXVOS Inventory.
Eslogan: "Conecta. Controla. Traza."

DATOS ACTUALES DEL INVENTARIO:
{$ctx}

CAPACIDADES:
- Responder consultas sobre activos TI y otros activos
- Informar sobre asignaciones, préstamos y devoluciones
- Interpretar estadísticas del inventario
- Sugerir el uso de los módulos de Reportes para exportar en Excel

REGLAS:
- Responde siempre en español
- Sé conciso y profesional
- No inventes datos fuera del contexto proporcionado
- Para reportes detallados sugiere: Módulo Reportes TI, Otros Activos o Auditoría Global con opción de exportar Excel
PROMPT;
    }

    private function demoReply(string $message, array $context): string
    {
        $lower = strtolower($message);

        if (str_contains($lower, 'inventario') || str_contains($lower, 'resumen') || str_contains($lower, 'activo')) {
            return "**Resumen del Inventario:**\n\n" .
                "- Activos TI: **{$context['total_ti']}**\n" .
                "- Otros Activos: **{$context['total_otro']}**\n" .
                "- Asignaciones activas: **{$context['asignaciones_activas']}**\n" .
                "- Préstamos activos: **{$context['prestamos_activos']}**\n" .
                ($context['prestamos_vencidos'] > 0 ? "- ⚠️ Préstamos vencidos: **{$context['prestamos_vencidos']}**\n" : '') .
                "\nPara reportes detallados con descarga en Excel, ve al menú **Reportes**.";
        }

        if (str_contains($lower, 'préstamo') || str_contains($lower, 'prestamo')) {
            return "**Estado de Préstamos TI:**\n\n" .
                "- Activos: **{$context['prestamos_activos']}**\n" .
                "- Vencidos: **{$context['prestamos_vencidos']}**\n\n" .
                "Gestiona los préstamos desde el módulo **Préstamos TI**.";
        }

        if (str_contains($lower, 'colaborador') || str_contains($lower, 'usuario')) {
            $extra = isset($context['top_colaboradores']) ? "\n\nTop colaboradores:\n" . $context['top_colaboradores'] : '';
            return "**Colaboradores activos:** {$context['colaboradores']}{$extra}\n\n" .
                "Para ver activos por colaborador, usa **Auditoría Global** → pestaña Asignaciones.";
        }

        return "Hola, soy **AXI**, tu asistente de AXVOS Inventory. 👋\n\n" .
            "El inventario tiene actualmente:\n" .
            "- **{$context['total_ti']}** activos TI\n" .
            "- **{$context['total_otro']}** otros activos\n" .
            "- **{$context['asignaciones_activas']}** asignaciones activas\n\n" .
            "_Para IA real, agrega `ANTHROPIC_API_KEY` en tu archivo `.env`._\n\n" .
            "¿En qué te puedo ayudar?";
    }
}
