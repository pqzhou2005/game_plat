<div class="space-y-4 p-2">
    <div>
        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">落地页链接</label>
        <div class="mt-1 flex gap-2">
            <input type="text" readonly value="{{ $landingUrl }}"
                   class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                   x-ref="landingUrl">
            <button type="button"
                    x-on:click="navigator.clipboard.writeText($refs.landingUrl.value); $el.innerText = '已复制'; setTimeout(() => $el.innerText = '复制', 2000)"
                    class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white hover:bg-primary-500">
                复制
            </button>
        </div>
    </div>
    <div>
        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">路径形式（外部推广）</label>
        <div class="mt-1 flex gap-2">
            <input type="text" readonly value="{{ $pathUrl }}"
                   class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                   x-ref="pathUrl">
            <button type="button"
                    x-on:click="navigator.clipboard.writeText($refs.pathUrl.value); $el.innerText = '已复制'; setTimeout(() => $el.innerText = '复制', 2000)"
                    class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white hover:bg-primary-500">
                复制
            </button>
        </div>
    </div>
    <div>
        <label class="text-sm font-medium text-gray-600 dark:text-gray-400">参数形式（外部推广）</label>
        <div class="mt-1 flex gap-2">
            <input type="text" readonly value="{{ $paramUrl }}"
                   class="flex-1 rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                   x-ref="paramUrl">
            <button type="button"
                    x-on:click="navigator.clipboard.writeText($refs.paramUrl.value); $el.innerText = '已复制'; setTimeout(() => $el.innerText = '复制', 2000)"
                    class="rounded-lg bg-primary-600 px-3 py-2 text-sm text-white hover:bg-primary-500">
                复制
            </button>
        </div>
    </div>
    <div class="pt-2 text-xs text-gray-400">
        推广码：<strong>{{ $promoteCode }}</strong>
    </div>
</div>
