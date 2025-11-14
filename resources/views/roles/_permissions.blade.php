{{-- Shared Permissions Partial --}}
@php
    use Illuminate\Support\Str;
@endphp

<div
    x-data="{ expanded: true }"
    class="space-y-6"
>
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                <i data-lucide="key" class="w-5 h-5 text-indigo-600 dark:text-indigo-400"></i>
                <span>Permissions</span>
            </h2>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Tick what this role is allowed to do. You can select by group or individually.
            </p>
        </div>

        <button
            type="button"
            @click="expanded = !expanded"
            class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
        >
            <i data-lucide="chevron-down" class="w-4 h-4" x-show="expanded"></i>
            <i data-lucide="chevron-right" class="w-4 h-4" x-show="!expanded"></i>
            <span x-show="expanded">Collapse All</span>
            <span x-show="!expanded">Expand All</span>
        </button>
    </div>

    {{-- Grouped Permissions --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">

        @forelse ($permissions as $group => $groupPermissions)
            @php
                $groupKey = Str::slug($group);
                $groupCount = $groupPermissions->count();
            @endphp

            <div
                x-data="{ allSelected: false }"
                class="py-4"
                x-show="expanded"
                x-transition.opacity.duration.200ms
            >
                {{-- Group Header --}}
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-gray-100 dark:bg-gray-900/50 text-gray-700 dark:text-gray-300">
                                {{ ucfirst($group) }}
                            </span>
                        </h3>
                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                            {{ $groupCount }} permission{{ $groupCount !== 1 ? 's' : '' }} in this module.
                        </p>
                    </div>

                    <button
                        type="button"
                        class="text-[11px] font-medium text-indigo-600 dark:text-indigo-400 hover:underline"
                        @click="
                            allSelected = !allSelected;
                            $root.querySelectorAll('[data-group={{ $groupKey }}]').forEach(cb => {
                                cb.checked = allSelected;
                            });
                        "
                    >
                        <span x-text="allSelected ? 'Deselect All' : 'Select All'"></span>
                    </button>
                </div>

                {{-- Group Checkboxes --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-2">
                    @foreach ($groupPermissions as $perm)
                        @php
                            $label = Str::after($perm->name, '.');
                            if ($label === '') {
                                $label = $perm->name;
                            }
                            $label = ucfirst(str_replace(['.', '_'], ' ', $label));
                            $isChecked = isset($rolePermissions) && in_array($perm->name, $rolePermissions);
                        @endphp

                        <label class="flex items-center gap-2 text-xs sm:text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $perm->name }}"
                                data-group="{{ $groupKey }}"
                                @checked($isChecked)
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500 focus:ring-offset-0"
                            >
                            <span class="leading-snug">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="py-4 text-sm text-gray-500 dark:text-gray-400">
                No permissions found. Make sure you have seeded or created some permissions first.
            </p>
        @endforelse

    </div>
</div>
