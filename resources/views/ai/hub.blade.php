@extends('adminlte::page')
@section('title', 'AXI — Asistente IA')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Asistente AXI</li>
        </ol>
    </nav>
@stop

@section('content')
<div class="row" style="height:calc(100vh - 160px);min-height:520px;">

    {{-- Sidebar: historial --}}
    <div class="col-lg-3 d-flex flex-column pr-lg-1" style="height:100%;">
        <div class="card shadow-sm d-flex flex-column mb-0" style="height:100%;border-top:3px solid #7c3aed;overflow:hidden;">
            <div class="card-header py-2 d-flex justify-content-between align-items-center" style="flex-shrink:0;">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('img/axi/axi-face.png') }}" alt="AXI"
                         style="width:28px;height:28px;object-fit:contain;border-radius:50%;margin-right:8px;"
                         onerror="this.outerHTML='<div style=\'width:28px;height:28px;border-radius:50%;background:linear-gradient(135deg,#5b21b6,#7c3aed);display:flex;align-items:center;justify-content:center;margin-right:8px;\'><i class=\'fas fa-robot text-white\' style=\'font-size:.7rem;\'></i></div>'">
                    <span class="font-weight-bold" style="font-size:.85rem;color:#7c3aed;">Conversaciones</span>
                </div>
                <form method="POST" action="{{ route('ai.new') }}">
                    @csrf
                    <button type="submit" class="btn btn-xs" style="background:#7c3aed;color:#fff;border-radius:50%;width:24px;height:24px;padding:0;" title="Nueva conversación">
                        <i class="fas fa-plus" style="font-size:.65rem;"></i>
                    </button>
                </form>
            </div>
            <div style="overflow-y:auto;flex:1;">
                @forelse($conversations as $conv)
                <div class="d-flex align-items-center border-bottom px-2 py-2 {{ $activeConversation?->id === $conv->id ? 'bg-light' : '' }}"
                     style="cursor:pointer;">
                    <a href="{{ route('ai.hub', ['conversation'=>$conv->id]) }}"
                       class="flex-grow-1 text-dark text-decoration-none" style="min-width:0;">
                        <div style="font-size:.78rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                            {{ $conv->title }}
                        </div>
                        <div style="font-size:.66rem;" class="text-muted">{{ $conv->updated_at->diffForHumans() }}</div>
                    </a>
                    <form method="POST" action="{{ route('ai.delete', $conv) }}" class="ml-1"
                          onsubmit="return confirm('¿Eliminar esta conversación?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="btn btn-xs text-muted border-0 bg-transparent" style="padding:2px 4px;">
                            <i class="fas fa-times" style="font-size:.6rem;"></i>
                        </button>
                    </form>
                </div>
                @empty
                <div class="text-center text-muted py-4" style="font-size:.78rem;">
                    <i class="fas fa-comments d-block mb-2" style="font-size:1.4rem;opacity:.25;"></i>
                    Sin conversaciones aún.<br>
                    <small>Escribe tu primera consulta.</small>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Área principal de chat --}}
    <div class="col-lg-9 d-flex flex-column pl-lg-1" style="height:100%;">
        <div class="card shadow-sm d-flex flex-column mb-0" style="height:100%;border-top:3px solid #7c3aed;overflow:hidden;">

            {{-- Header --}}
            <div class="card-header py-2 d-flex align-items-center justify-content-between" style="flex-shrink:0;">
                <div class="d-flex align-items-center">
                    <img src="{{ asset('img/axi/axi-hold.png') }}" alt="AXI"
                         style="width:36px;height:36px;object-fit:contain;margin-right:10px;"
                         onerror="this.outerHTML='<div style=\'width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#5b21b6,#7c3aed);display:flex;align-items:center;justify-content:center;margin-right:10px;\'><i class=\'fas fa-robot text-white\'></i></div>'">
                    <div>
                        <div class="font-weight-bold" style="color:#7c3aed;font-size:.95rem;">AXI</div>
                        <div style="font-size:.7rem;" class="text-muted">Asistente AXVOS Inventory · Conecta. Controla. Traza.</div>
                    </div>
                </div>
                <div class="d-flex align-items-center" style="gap:6px;">
                    @if($activeConversation)
                    <a href="{{ route('ai.export', $activeConversation) }}"
                       class="btn btn-xs btn-outline-secondary" title="Exportar conversación">
                        <i class="fas fa-download mr-1"></i> Exportar
                    </a>
                    @endif
                    @if(!config('services.anthropic.key'))
                    <span class="badge badge-warning" style="font-size:.65rem;">
                        <i class="fas fa-exclamation-triangle mr-1"></i>Modo demo (sin API key)
                    </span>
                    @endif
                </div>
            </div>

            {{-- Mensajes --}}
            <div id="chatMessages" style="overflow-y:auto;flex:1;padding:1rem;background:#fafafa;">

                @if($messages->isEmpty())
                <div class="text-center py-5" id="welcomeMsg">
                    <img src="{{ asset('img/axi/axi-hold.png') }}" alt="AXI"
                         style="width:80px;height:80px;object-fit:contain;margin-bottom:12px;"
                         onerror="this.style.display='none'">
                    <h5 style="color:#7c3aed;">¡Hola! Soy AXI</h5>
                    <p class="text-muted" style="font-size:.84rem;max-width:380px;margin:0 auto 16px;">
                        Tu asistente de AXVOS Inventory. Pregúntame sobre activos, asignaciones, préstamos y más.
                    </p>
                    <div class="row justify-content-center" style="max-width:460px;margin:0 auto;">
                        @foreach(['¿Cuántos activos TI disponibles?','¿Hay préstamos vencidos?','Dame un resumen del inventario','¿Cuántas asignaciones activas?'] as $s)
                        <div class="col-6 mb-2">
                            <button class="btn btn-sm btn-outline-secondary btn-block text-left"
                                    style="font-size:.75rem;white-space:normal;"
                                    onclick="sendSuggestion(this)">{{ $s }}</button>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

                @foreach($messages as $msg)
                @include('ai._message', ['msg' => $msg])
                @endforeach

                <div id="typingIndicator" style="display:none;" class="d-flex mb-3">
                    <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#5b21b6,#7c3aed);display:flex;align-items:center;justify-content:center;margin-right:8px;flex-shrink:0;">
                        <i class="fas fa-robot text-white" style="font-size:.6rem;"></i>
                    </div>
                    <div class="px-3 py-2" style="background:#fff;border:1px solid #e9ecef;border-radius:18px 18px 18px 4px;">
                        <span class="typing-dots"><span>.</span><span>.</span><span>.</span></span>
                    </div>
                </div>
            </div>

            {{-- Input --}}
            <div class="border-top bg-white px-3 py-2" style="flex-shrink:0;">
                <div class="d-flex align-items-end" style="gap:8px;">
                    <textarea id="chatInput" class="form-control" rows="1"
                              placeholder="Escribe tu consulta para AXI..."
                              style="border-radius:20px;border:1.5px solid #e9d8fd;resize:none;font-size:.84rem;padding:8px 16px;max-height:120px;overflow-y:auto;"></textarea>
                    <button id="sendBtn" onclick="sendMessage()"
                            class="btn d-flex align-items-center justify-content-center"
                            style="border-radius:50%;width:40px;height:40px;min-width:40px;background:#7c3aed;color:#fff;flex-shrink:0;padding:0;">
                        <i class="fas fa-paper-plane" style="font-size:.8rem;"></i>
                    </button>
                </div>
                <div style="font-size:.66rem;" class="text-muted mt-1 text-center">
                    AXI puede cometer errores. Verifica información crítica. · <kbd>Enter</kbd> para enviar
                </div>
            </div>

        </div>
    </div>
</div>

{{-- Hidden CSRF for JS --}}
<meta name="csrf-token" content="{{ csrf_token() }}">
@stop

@section('css')
<style>
.typing-dots span {
    display:inline-block;animation:axiDot 1.4s infinite both;
    font-size:1.1rem;color:#7c3aed;line-height:1;
}
.typing-dots span:nth-child(2){animation-delay:.2s;}
.typing-dots span:nth-child(3){animation-delay:.4s;}
@keyframes axiDot{0%,80%,100%{opacity:0;}40%{opacity:1;}}
#chatMessages::-webkit-scrollbar{width:4px;}
#chatMessages::-webkit-scrollbar-thumb{background:rgba(124,58,237,.25);border-radius:3px;}
.axi-bubble-ai   {background:#fff;border:1px solid #e9ecef;border-radius:18px 18px 18px 4px;}
.axi-bubble-user {background:#7c3aed;color:#fff;border-radius:18px 18px 4px 18px;}
</style>
@stop

@section('js')
<script>
const chatMessages  = document.getElementById('chatMessages');
const chatInput     = document.getElementById('chatInput');
const sendBtn       = document.getElementById('sendBtn');
const typingInd     = document.getElementById('typingIndicator');
let   conversationId = '{{ $activeConversation?->id }}';
const userInitial    = '{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}';

function scrollBottom(){ chatMessages.scrollTop = chatMessages.scrollHeight; }
scrollBottom();

chatInput.addEventListener('input', function(){
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});
chatInput.addEventListener('keydown', function(e){
    if (e.key === 'Enter' && !e.shiftKey){ e.preventDefault(); sendMessage(); }
});

function sendSuggestion(btn){ chatInput.value = btn.textContent.trim(); sendMessage(); }

function renderMd(text){
    return text
        .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
        .replace(/\*\*(.*?)\*\*/g,'<strong>$1</strong>')
        .replace(/\*(.*?)\*/g,'<em>$1</em>')
        .replace(/^[-•] (.+)$/gm,'<li>$1</li>')
        .replace(/(<li>.*<\/li>)/gs,'<ul class="mb-1 pl-3">$1</ul>')
        .replace(/\n/g,'<br>');
}

function appendMsg(role, content, time){
    const welcome = document.getElementById('welcomeMsg');
    if (welcome) welcome.remove();

    const div = document.createElement('div');
    div.className = 'd-flex mb-3 ' + (role==='user' ? 'justify-content-end' : '');

    const avatarAi = `<div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#5b21b6,#7c3aed);display:flex;align-items:center;justify-content:center;margin-right:8px;flex-shrink:0;margin-top:4px;">
        <i class="fas fa-robot text-white" style="font-size:.6rem;"></i></div>`;
    const avatarUser = `<div style="width:26px;height:26px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:bold;margin-left:8px;flex-shrink:0;margin-top:4px;">${userInitial}</div>`;

    div.innerHTML = `
        ${role==='assistant' ? avatarAi : ''}
        <div style="max-width:78%;">
            <div class="px-3 py-2 ${role==='user' ? 'axi-bubble-user' : 'axi-bubble-ai'}">
                <div style="font-size:.83rem;line-height:1.5;">${role==='user'
                    ? content.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>')
                    : renderMd(content)}</div>
            </div>
            <div style="font-size:.63rem;" class="text-muted mt-1 ${role==='user'?'text-right':''}">${time}</div>
        </div>
        ${role==='user' ? avatarUser : ''}`;

    chatMessages.insertBefore(div, typingInd);
    scrollBottom();
}

async function sendMessage(){
    const msg = chatInput.value.trim();
    if (!msg || sendBtn.disabled) return;

    const time = new Date().toLocaleTimeString('es-CO',{hour:'2-digit',minute:'2-digit'});
    appendMsg('user', msg, time);
    chatInput.value = ''; chatInput.style.height = 'auto';
    sendBtn.disabled = true;
    typingInd.style.display = 'flex';
    scrollBottom();

    try {
        const resp = await fetch('{{ route("ai.chat") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ message: msg, conversation_id: conversationId || null }),
        });
        const data = await resp.json();
        typingInd.style.display = 'none';

        if (data.reply) {
            appendMsg('assistant', data.reply, time);
            if (data.conversation_id && !conversationId) {
                conversationId = data.conversation_id;
                const url = new URL(window.location);
                url.searchParams.set('conversation', conversationId);
                window.history.replaceState({}, '', url);
                setTimeout(() => window.location.reload(), 2500);
            }
        } else {
            appendMsg('assistant', 'Error al procesar la respuesta.', time);
        }
    } catch(e) {
        typingInd.style.display = 'none';
        appendMsg('assistant', 'Error de conexión. Intenta de nuevo.', time);
    }
    sendBtn.disabled = false;
}
</script>
@stop
