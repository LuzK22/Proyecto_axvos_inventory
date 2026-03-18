{{--
    Partial de notificaciones y alertas UX
    Uso: @include('partials._alerts')
    Soporta: success, error, warning, info
--}}

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-check-circle fa-lg mr-2"></i>
            <div>{!! session('success') !!}</div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-circle fa-lg mr-2"></i>
            <div>{!! session('error') !!}</div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-exclamation-triangle fa-lg mr-2"></i>
            <div>{!! session('warning') !!}</div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('info'))
    <div class="alert alert-info alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-center">
            <i class="fas fa-info-circle fa-lg mr-2"></i>
            <div>{!! session('info') !!}</div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <div class="d-flex align-items-start">
            <i class="fas fa-times-circle fa-lg mr-2 mt-1"></i>
            <div>
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul class="mb-0 mt-1 pl-3">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
        <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif
