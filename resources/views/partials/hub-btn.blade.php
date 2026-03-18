{{--
    Partial: hub-btn
    Uso: @include('partials.hub-btn', ['href'=>'...', 'color'=>'#3a57e8', 'icon'=>'fas fa-plus', 'label'=>'Crear', 'sub'=>'Descripción', 'soon'=>false])
--}}
@php $soon = $soon ?? false; @endphp
<div class="col-md-4 mb-3">
    @if($soon)
        <div class="hub-btn hub-btn-soon">
            <div class="hub-btn-icon"><i class="{{ $icon }}"></i></div>
            <div class="hub-btn-text">
                <strong>{{ $label }}</strong>
                <small>{{ $sub ?? 'Próximamente' }}</small>
            </div>
            <span class="hub-soon-badge">Próximamente</span>
        </div>
    @else
        <a href="{{ $href }}" class="hub-btn" style="background:linear-gradient(135deg,{{ $color }},{{ $colorDark ?? $color }});">
            <div class="hub-btn-icon"><i class="{{ $icon }}"></i></div>
            <div class="hub-btn-text">
                <strong>{{ $label }}</strong>
                @if(isset($sub))<small>{{ $sub }}</small>@endif
            </div>
        </a>
    @endif
</div>
