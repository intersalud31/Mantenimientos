@props([
    'action' => '',
    'name' => 'q',
    'label' => 'Buscar',
    'placeholder' => 'Buscar',
    'value' => '',
    'target' => '',
    'debounce' => 500,
    'loadingLabel' => 'Buscando...',
    'pageName' => 'page',
    'pageValue' => '1',
])

<form
    method="GET"
    action="{{ $action }}"
    class="panel-live-search"
    data-panel-live-search="true"
    data-panel-live-search-target="{{ $target }}"
    data-panel-live-search-debounce="{{ $debounce }}"
>
    <input type="hidden" name="{{ $pageName }}" value="{{ $pageValue }}">

    @isset($hidden)
        {{ $hidden }}
    @endisset

    <label class="panel-filter-field panel-filter-field--search panel-live-search-field" for="{{ $name }}">
        <span>{{ $label }}</span>
        <div class="panel-live-search-input-wrap">
            <input
                id="{{ $name }}"
                type="text"
                name="{{ $name }}"
                value="{{ $value }}"
                placeholder="{{ $placeholder }}"
                data-panel-live-search-input
                autocomplete="off"
            >
            <span class="panel-live-search-loading" data-panel-live-search-loading hidden>{{ $loadingLabel }}</span>
        </div>
    </label>
</form>
