<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/invitation.css'])
    <style>
        html, body { margin: 0; }
        body { background: #fff; }

        .lyp-frame-row { position: relative; }
        .lyp-frame-row-toolbar {
            position: absolute; top: 0; right: 0; z-index: 30;
            display: flex; opacity: 0; transition: opacity .15s;
            background: rgba(17, 24, 39, .85); border-radius: 0 0 0 .375rem;
        }
        .lyp-frame-row:hover > .lyp-frame-row-toolbar,
        .lyp-frame-row.lyp-frame-selected > .lyp-frame-row-toolbar { opacity: 1; }
        .lyp-frame-row.lyp-frame-selected { outline: 2px dashed #6366f1; outline-offset: -2px; }
        .lyp-frame-row.lyp-frame-row-dragging { opacity: .35; }
        .lyp-frame-row-drop { height: 0; overflow: hidden; transition: height .15s; border: 2px dashed #818cf8; border-radius: .375rem; background: rgba(99, 102, 241, .06); margin: 0; }
        .lyp-frame-row-drop.lyp-frame-drop-active { height: 1.5rem; margin: .25rem 0; }

        .lyp-frame-col { position: relative; outline: 1px dashed transparent; border-radius: .25rem; padding-top: 1.1rem; }
        .lyp-frame-col:hover, .lyp-frame-col.lyp-frame-drop-target { outline-color: #a5b4fc; background: rgba(99, 102, 241, .03); }
        .lyp-frame-col-toolbar {
            position: absolute; top: 0; left: 0; z-index: 25; display: none; align-items: center; gap: .25rem;
            background: rgba(17, 24, 39, .85); border-radius: 0 0 .375rem 0; padding: .05rem;
        }
        .lyp-frame-col:hover .lyp-frame-col-toolbar { display: flex; }
        .lyp-frame-col-toolbar span { color: #d1d5db; font-size: .625rem; padding: 0 .25rem; }
        .lyp-frame-col-toolbar button { padding: .25rem; color: #fff; background: none; border: none; cursor: pointer; line-height: 0; }
        .lyp-frame-col-toolbar button:hover { background: rgba(255, 255, 255, .15); }
        .lyp-frame-col-toolbar svg { width: .7rem; height: .7rem; }
        .lyp-frame-col-add {
            display: flex; align-items: center; justify-content: center; gap: .375rem; width: 100%;
            padding: .5rem; margin-top: .5rem; border: 1px dashed #d1d5db; border-radius: .375rem;
            background: transparent; color: #6b7280; font: 500 .75rem/1 ui-sans-serif, system-ui, sans-serif; cursor: pointer;
        }
        .lyp-frame-col-add:hover { border-color: #6366f1; color: #6366f1; }
        .lyp-frame-col-add svg { width: .875rem; height: .875rem; }
        .lyp-frame-empty-col { padding: 1.5rem; text-align: center; font: 500 .75rem/1 ui-sans-serif, system-ui, sans-serif; color: #9ca3af; border: 1px dashed #e5e7eb; border-radius: .375rem; }

        .lyp-frame-widget { position: relative; cursor: pointer; outline: 1px solid transparent; border-radius: .25rem; }
        .lyp-frame-widget:hover { outline: 1px dashed #6366f1; }
        .lyp-frame-widget.lyp-frame-selected { outline: 2px solid #6366f1; }
        .lyp-frame-widget.lyp-frame-dragging { opacity: .35; }
        .lyp-frame-widget-toolbar {
            position: absolute; top: -1.6rem; right: 0; z-index: 30; display: none;
            background: rgba(17, 24, 39, .9); border-radius: .25rem .25rem 0 0;
        }
        .lyp-frame-widget:hover .lyp-frame-widget-toolbar,
        .lyp-frame-widget.lyp-frame-selected .lyp-frame-widget-toolbar { display: flex; }
        .lyp-frame-row-toolbar button, .lyp-frame-widget-toolbar button {
            padding: .3rem; color: #fff; background: none; border: none; cursor: pointer; line-height: 0;
        }
        .lyp-frame-row-toolbar button:hover, .lyp-frame-widget-toolbar button:hover { background: rgba(255, 255, 255, .15); }
        .lyp-frame-row-toolbar svg, .lyp-frame-widget-toolbar svg { width: .8rem; height: .8rem; }
        .lyp-frame-widget-drop { height: 0; overflow: hidden; transition: height .15s; border: 2px dashed #818cf8; border-radius: .375rem; background: rgba(99, 102, 241, .06); margin: 0; }
        .lyp-frame-widget-drop.lyp-frame-drop-active { height: 2rem; margin: .25rem 0; }
        .lyp-frame-empty { padding: 4rem 1.5rem; text-align: center; color: #9ca3af; font: 500 .875rem/1 ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-white font-sans text-gray-900 antialiased">
@php
    $widthMap = [1 => '1/12', 2 => '2/12', 3 => '3/12', 4 => '4/12', 5 => '5/12', 6 => '6/12', 7 => '7/12', 8 => '8/12', 9 => '9/12', 10 => '10/12', 11 => '11/12', 12 => 'full'];

    $editIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/></svg>';
    $dupIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 17.25v3.375c0 .621-.504 1.125-1.125 1.125h-9.75a1.125 1.125 0 01-1.125-1.125V7.875c0-.621.504-1.125 1.125-1.125H6.75a9.06 9.06 0 011.5.124m7.5 10.376h3.375c.621 0 1.125-.504 1.125-1.125V11.25c0-4.46-3.243-8.161-7.5-8.876a9.06 9.06 0 00-1.5-.124H9.375c-.621 0-1.125.504-1.125 1.125v3.5m7.5 10.375H9.375a1.125 1.125 0 01-1.125-1.125v-9.25m12 6.625v-1.875a3.375 3.375 0 00-3.375-3.375h-1.5a1.125 1.125 0 01-1.125-1.125v-1.5a3.375 3.375 0 00-3.375-3.375H9.75"/></svg>';
    $trashIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>';
    $plusIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>';
    $gearIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
    $leftIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>';
    $rightIcon = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>';
@endphp

<div id="lyp-frame-root">
@forelse($rows as $rowIndex => $row)
    <div class="lyp-frame-row-drop" data-lyp-action="row-drop" data-drop-index="{{ $rowIndex }}"></div>
    <div class="lyp-frame-row" data-row-id="{{ $row['id'] }}">
        <div class="lyp-frame-row-toolbar">
            <button type="button" data-lyp-action="row-add-column" data-row-id="{{ $row['id'] }}" title="Add column">{!! $plusIcon !!}</button>
            <button type="button" data-lyp-action="row-duplicate" data-row-id="{{ $row['id'] }}" title="Duplicate row">{!! $dupIcon !!}</button>
            <button type="button" data-lyp-action="row-edit" data-row-id="{{ $row['id'] }}" title="Row settings">{!! $gearIcon !!}</button>
            <span data-lyp-action="row-drag-handle" data-row-id="{{ $row['id'] }}" draggable="true" title="Drag to reorder" style="display:flex;align-items:center;cursor:grab;padding:.3rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:.8rem;height:.8rem;color:#fff;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/></svg>
            </span>
            <button type="button" data-lyp-action="row-delete" data-row-id="{{ $row['id'] }}" title="Delete row">{!! $trashIcon !!}</button>
        </div>
        <div class="flex flex-wrap {{ $containerClass }} mx-auto">
            @foreach($row['columns'] as $col)
                @php
                    $span = $col['span'];
                    $sm = $widthMap[$span['sm'] ?? 12] ?? 'full';
                    $md = $widthMap[$span['md'] ?? 12] ?? 'full';
                    $lg = $widthMap[$span['lg'] ?? 12] ?? 'full';
                    $xl = $widthMap[$span['xl'] ?? 12] ?? 'full';
                    $isOnly = $loop->first && $loop->last;
                    $gutter = $isOnly ? '' : ($loop->first ? 'md:pr-2' : ($loop->last ? 'md:pl-2' : 'md:px-2'));
                @endphp
                <div class="lyp-frame-col w-{{ $sm }} md:w-{{ $md }} lg:w-{{ $lg }} xl:w-{{ $xl }} {{ $gutter }} space-y-4" data-col-id="{{ $col['id'] }}" data-row-id="{{ $row['id'] }}">
                    <div class="lyp-frame-col-toolbar">
                        <span>Col {{ $loop->iteration }}</span>
                        <button type="button" data-lyp-action="col-move-left" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" @disabled($loop->first) title="Move left">{!! $leftIcon !!}</button>
                        <button type="button" data-lyp-action="col-move-right" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" @disabled($loop->last) title="Move right">{!! $rightIcon !!}</button>
                        <button type="button" data-lyp-action="col-edit" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" title="Column settings">{!! $gearIcon !!}</button>
                        <button type="button" data-lyp-action="col-delete" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" title="Delete column">{!! $trashIcon !!}</button>
                    </div>
                    @forelse($col['widgets'] as $widgetIndex => $widget)
                        <div class="lyp-frame-widget-drop" data-lyp-action="widget-drop" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" data-drop-index="{{ $widgetIndex }}"></div>
                        <div class="lyp-frame-widget" data-widget-id="{{ $widget['id'] }}" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" draggable="true">
                            <div class="lyp-frame-widget-toolbar">
                                <button type="button" data-lyp-action="widget-edit" data-widget-id="{{ $widget['id'] }}" title="Edit">{!! $editIcon !!}</button>
                                <button type="button" data-lyp-action="widget-duplicate" data-widget-id="{{ $widget['id'] }}" title="Duplicate">{!! $dupIcon !!}</button>
                                <button type="button" data-lyp-action="widget-delete" data-widget-id="{{ $widget['id'] }}" title="Delete">{!! $trashIcon !!}</button>
                            </div>
                            {!! $widget['html'] !!}
                        </div>
                    @empty
                        <div class="lyp-frame-empty-col" data-lyp-action="widget-drop" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" data-drop-index="0">Drop a widget here</div>
                    @endforelse
                    <div class="lyp-frame-widget-drop" data-lyp-action="widget-drop" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}" data-drop-index="{{ count($col['widgets']) }}"></div>
                    <button type="button" class="lyp-frame-col-add" data-lyp-action="col-add-widget" data-row-id="{{ $row['id'] }}" data-col-id="{{ $col['id'] }}">{!! $plusIcon !!} Add Widget</button>
                </div>
            @endforeach
        </div>
    </div>
@empty
    <div class="lyp-frame-empty">No rows yet — use "Add Row" below the canvas, or drag a widget in from the left panel.</div>
@endforelse
    <div class="lyp-frame-row-drop" data-lyp-action="row-drop" data-drop-index="{{ count($rows) }}"></div>
</div>
</body>
</html>
