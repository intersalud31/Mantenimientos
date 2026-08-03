@props([
    'actions' => [],
    'label' => 'Acciones',
    'menuId' => null,
    'align' => 'end',
])

@php
    $menuId = $menuId ?: 'panel-action-menu-' . uniqid();
    $items = array_values(array_filter($actions, static fn ($item) => is_array($item) && !empty($item['label'])));
@endphp

@if ($items !== [])
    <div class="panel-action-menu panel-action-menu--{{ $align === 'start' ? 'start' : 'end' }}" data-panel-action-menu>
        <button
            type="button"
            class="panel-action-menu-trigger"
            data-panel-action-menu-trigger
            aria-haspopup="menu"
            aria-expanded="false"
            aria-controls="{{ $menuId }}"
            aria-label="{{ $label }}"
        >
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
            <span aria-hidden="true"></span>
        </button>

        <div
            id="{{ $menuId }}"
            class="panel-action-menu-panel"
            data-panel-action-menu-panel
            role="menu"
            aria-label="{{ $label }}"
            hidden
        >
            @foreach ($items as $item)
                @php
                    $type = $item['type'] ?? 'link';
                    $variant = $item['variant'] ?? '';
                    $itemClass = 'panel-action-menu-item';
                    $itemClass .= $variant !== '' ? ' panel-action-menu-item--' . $variant : '';
                    $itemAttributes = is_array($item['attributes'] ?? null) ? $item['attributes'] : [];
                @endphp

                @if ($type === 'button')
                    <button
                        type="button"
                        class="{{ $itemClass }}"
                        data-panel-action-menu-item
                        role="menuitem"
                        @foreach ($itemAttributes as $attribute => $value)
                            @if (is_bool($value))
                                @if ($value) {{ $attribute }} @endif
                            @else
                                {{ $attribute }}="{{ e($value) }}"
                            @endif
                        @endforeach
                    >
                        {{ $item['label'] }}
                    </button>
                @elseif ($type === 'form')
                    <form
                        method="{{ strtoupper((string) ($item['method'] ?? 'POST')) === 'GET' ? 'GET' : 'POST' }}"
                        action="{{ $item['action'] ?? '#' }}"
                        class="panel-action-menu-form"
                        @if (!empty($item['confirm'])) onsubmit="return confirm('{{ addslashes($item['confirm']) }}');" @endif
                    >
                        @if (strtoupper((string) ($item['method'] ?? 'POST')) !== 'GET')
                            @csrf

                            @php $formMethod = strtoupper((string) ($item['method'] ?? 'POST')); @endphp
                            @if (!in_array($formMethod, ['POST', 'GET'], true))
                                @method($formMethod)
                            @endif
                        @endif

                        @foreach (($item['fields'] ?? []) as $field => $value)
                            <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                        @endforeach

                        <button
                            type="submit"
                            class="{{ $itemClass }}"
                            data-panel-action-menu-item
                            role="menuitem"
                            @foreach ($itemAttributes as $attribute => $value)
                                @if (is_bool($value))
                                    @if ($value) {{ $attribute }} @endif
                                @else
                                    {{ $attribute }}="{{ e($value) }}"
                                @endif
                            @endforeach
                        >
                            {{ $item['label'] }}
                        </button>
                    </form>
                @elseif ($type === 'text')
                    <span class="{{ $itemClass }}" role="menuitem" aria-disabled="true">
                        {{ $item['label'] }}
                    </span>
                @else
                    <a
                        href="{{ $item['href'] ?? '#' }}"
                        class="{{ $itemClass }}"
                        data-panel-action-menu-item
                        role="menuitem"
                        @if (!empty($item['target'])) target="{{ $item['target'] }}" @endif
                        @if (!empty($item['rel'])) rel="{{ $item['rel'] }}" @endif
                        @if (!empty($item['download'])) download="{{ $item['download'] }}" @endif
                        @foreach ($itemAttributes as $attribute => $value)
                            @if (is_bool($value))
                                @if ($value) {{ $attribute }} @endif
                            @else
                                {{ $attribute }}="{{ e($value) }}"
                            @endif
                        @endforeach
                    >
                        {{ $item['label'] }}
                    </a>
                @endif
            @endforeach
        </div>
    </div>
@endif
