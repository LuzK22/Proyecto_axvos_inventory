@php
    $isUser = $msg->role === 'user';
    $initial = strtoupper(substr(auth()->user()->name, 0, 1));
@endphp
<div class="d-flex mb-3 {{ $isUser ? 'justify-content-end' : '' }}">
    @if(!$isUser)
    <div style="width:26px;height:26px;border-radius:50%;background:linear-gradient(135deg,#5b21b6,#7c3aed);display:flex;align-items:center;justify-content:center;margin-right:8px;flex-shrink:0;margin-top:4px;">
        <i class="fas fa-robot text-white" style="font-size:.6rem;"></i>
    </div>
    @endif
    <div style="max-width:78%;">
        <div class="px-3 py-2 {{ $isUser ? 'axi-bubble-user' : 'axi-bubble-ai' }}">
            <div style="font-size:.83rem;line-height:1.5;">
                @if($isUser)
                    {!! nl2br(e($msg->content)) !!}
                @else
                    {!! \Illuminate\Support\Str::of($msg->content)
                        ->replace('&', '&amp;')
                        ->replace('<', '&lt;')
                        ->replace('>', '&gt;')
                        ->replaceMatches('/\*\*(.*?)\*\*/', '<strong>$1</strong>')
                        ->replaceMatches('/\*(.*?)\*/', '<em>$1</em>')
                        ->replaceMatches('/^[-•] (.+)$/m', '<li>$1</li>')
                        ->replace("\n", '<br>') !!}
                @endif
            </div>
        </div>
        <div style="font-size:.63rem;" class="text-muted mt-1 {{ $isUser ? 'text-right' : '' }}">
            {{ $msg->created_at->format('H:i') }}
        </div>
    </div>
    @if($isUser)
    <div style="width:26px;height:26px;border-radius:50%;background:#e9ecef;display:flex;align-items:center;justify-content:center;font-size:.7rem;font-weight:bold;margin-left:8px;flex-shrink:0;margin-top:4px;">
        {{ $initial }}
    </div>
    @endif
</div>
