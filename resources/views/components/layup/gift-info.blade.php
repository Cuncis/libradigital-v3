@php $vis = \Crumbls\Layup\View\BaseView::visibilityClasses($data['hide_on'] ?? []); @endphp
<div @if(!empty($data['id']))id="{{ $data['id'] }}"@endif
     class="{{ $vis }} {{ $data['class'] ?? '' }} max-w-md mx-auto"
     style="{{ \Crumbls\Layup\View\BaseView::buildInlineStyles($data) }}"
     {!! \Crumbls\Layup\View\BaseView::animationAttributes($data) !!}
>
    @if(!empty($data['title']))
        <h3 class="text-lg font-semibold mb-2 text-center">{{ $data['title'] }}</h3>
    @endif

    @if(!empty($data['intro_text']))
        <p class="text-sm text-gray-600 dark:text-gray-400 text-center mb-4">{{ $data['intro_text'] }}</p>
    @endif

    <div class="space-y-3">
        @foreach (($data['accounts'] ?? []) as $account)
            @if (!empty($account['account_number']))
                <div
                    x-data="{ copied: false }"
                    class="rounded-lg border border-gray-200 dark:border-gray-700 p-4"
                >
                    <p class="text-xs uppercase tracking-wide text-gray-500">{{ $account['bank_name'] ?? '' }}</p>
                    <div class="mt-1 flex items-center justify-between gap-3">
                        <div>
                            <p class="font-mono text-base">{{ $account['account_number'] }}</p>
                            @if (!empty($account['account_holder']))
                                <p class="text-sm text-gray-500">{{ $account['account_holder'] }}</p>
                            @endif
                        </div>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText('{{ $account['account_number'] }}'); copied = true; setTimeout(() => copied = false, 2000)"
                            class="shrink-0 rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                        >
                            <span x-show="!copied">Copy</span>
                            <span x-show="copied" x-cloak>Copied!</span>
                        </button>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    @if (!empty($data['digital_link']))
        <a
            href="{{ $data['digital_link'] }}"
            target="_blank"
            rel="noopener noreferrer"
            class="mt-4 block w-full rounded-lg bg-gray-900 px-4 py-2 text-center text-sm font-medium text-white hover:bg-gray-700"
        >
            {{ $data['digital_link_label'] ?? 'Send a Digital Gift' }}
        </a>
    @endif
</div>
