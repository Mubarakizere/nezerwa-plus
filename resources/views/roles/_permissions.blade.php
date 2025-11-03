{{-- Shared Permissions Partial --}}
<div
    x-data="{ expanded: true }"
    class="space-y-6"
>
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-100">
            Permissions
        </h2>

        <button
            type="button"
            @click="expanded = !expanded"
            class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"
        >
            <span x-show="expanded">Collapse All</span>
            <span x-show="!expanded">Expand All</span>
        </button>
    </div>

    {{-- Grouped Permissions --}}
    <div class="divide-y divide-gray-200 dark:divide-gray-700">
        @foreach ($permissions as $group => $groupPermissions)
            <div x-data="{ allSelected: false }" class="py-4" x-show="expanded" x-transition.opacity.duration.200ms>

                {{-- Group Header --}}
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-medium text-gray-800 dark:text-gray-100">
                        {{ ucfirst($group) }}
                    </h3>

                    <button
                        type="button"
                        class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"
                        @click="
                            allSelected = !allSelected;
                            $root.querySelectorAll('[data-group={{ Str::slug($group) }}]').forEach(cb => cb.checked = allSelected);
                        "
                    >
                        <span x-text="allSelected ? 'Deselect All' : 'Select All'"></span>
                    </button>
                </div>

                {{-- Group Checkboxes --}}
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                    @foreach ($groupPermissions as $perm)
                        <label class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                            <input
                                type="checkbox"
                                name="permissions[]"
                                value="{{ $perm->name }}"
                                data-group="{{ Str::slug($group) }}"
                                {{ isset($rolePermissions) && in_array($perm->name, $rolePermissions) ? 'checked' : '' }}
                                class="rounded border-gray-300 dark:border-gray-600 text-indigo-600 focus:ring-indigo-500"
                            >
                            <span>{{ ucfirst(str_replace(['.', '_'], ' ', Str::after($perm->name, '.'))) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
