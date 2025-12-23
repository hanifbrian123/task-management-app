<x-app-layout>
    <div 
        x-data="completedTasksApp"
        x-init="loadCompleted()"
        class="p-6 max-w-4xl mx-auto"
    >

        <h1 class="text-xl font-semibold mb-4">Completed Tasks</h1>

        <!-- Loading -->
        <template x-if="loading">
            <p class="text-gray-500 text-sm">Loading...</p>
        </template>

        <!-- Groups -->
        <template x-for="[date, tasks] in Object.entries(groups)" :key="date">
            <div class="mb-6">
                <h2 class="text-sm font-bold text-gray-600 mb-2"
                    x-text="date">
                </h2>

                <div class="space-y-2">
                    <template x-for="task in tasks" :key="task.id">
                        <div class="bg-gray-100 rounded px-3 py-2 flex items-center gap-2">
                            <input 
                                type="checkbox"
                                @change="uncompleteTask(task)"
                                checked
                            >
                            <span class="line-through text-gray-500" x-text="task.title"></span>
                        </div>
                    </template>
                </div>
            </div>
        </template>

        <!-- Empty -->
        <template x-if="!loading && Object.keys(groups).length === 0">
            <p class="text-gray-400">No completed tasks yet.</p>
        </template>

    </div>

    <script>
    document.addEventListener("alpine:init", () => {
        Alpine.data("completedTasksApp", () => ({
            loading: false,
            groups: {},

            async loadCompleted() {
                this.loading = true;
                try {
                    const res = await fetch('/tasks/completed');
                    const data = await res.json();
                    this.groups = data.groups ?? {};
                } finally {
                    this.loading = false;
                }
            },

            async uncompleteTask(task) {
                // optimistic
                const oldGroups = JSON.parse(JSON.stringify(this.groups));

                try {
                    await fetch(`/tasks/${task.id}/toggle-status`, {
                        method: 'PATCH',
                        headers: {
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        }
                    });

                    // remove from UI
                    this.loadCompleted();
                } catch {
                    // rollback
                    this.groups = oldGroups;
                    alert("Failed to update task");
                }
            }
        }))
    });
    </script>

</x-app-layout>
