<x-app-layout>

<div 
    x-data="taskApp()" 
    x-init="init()"
    class="grid grid-cols-5 h-[calc(100vh-4rem)]"
>
    <!-- PANEL KIRI -->
    <div class="col-span-2 border-r bg-gray-50 p-4 overflow-y-auto">

        <!-- CATEGORY FILTER -->
        <div class="flex gap-2 mb-4 flex-wrap">
            <button
                class="px-3 py-1 rounded text-sm"
                :class="selectedCategory === null 
                    ? 'bg-indigo-600 text-white' 
                    : 'bg-gray-200'"
                @click="selectedCategory = null; loadTasks()"
            >
                All
            </button>

            <template x-for="cat in categories" :key="cat.id">
                <button
                    class="px-3 py-1 rounded text-sm"
                    :class="selectedCategory === cat.id
                        ? 'bg-indigo-600 text-white'
                        : 'bg-gray-200'"
                    @click="selectedCategory = cat.id; loadTasks()"
                    x-text="cat.name"
                ></button>
            </template>
        </div>

        <!-- LOADING -->
        <div x-show="loadingTasks" class="text-sm text-gray-400">
            Loading tasks...
        </div>

        <!-- TASK LIST -->
        <template x-if="!loadingTasks && tasks.length === 0">
            <div class="text-sm text-gray-400">
                Tidak ada task
            </div>
        </template>

        <template x-for="task in tasks" :key="task.id">
            <div
                class="p-3 mb-2 rounded cursor-pointer flex items-center gap-2 bg-white hover:bg-gray-100"
                @click="selectTask(task.id)"
            >
                <input 
                    type="checkbox"
                    @click.stop="toggleStatus(task.id)"
                    :checked="task.status === 'done'"
                >
                <span x-text="task.title"></span>
            </div>
        </template>
    </div>


    <!-- PANEL KANAN -->
    <template x-if="selectedTask">
        <div class="col-span-3 p-6 bg-gray-100">
            <div class="bg-white rounded-lg shadow p-6 max-w-3xl space-y-6">

                <!-- HEADER -->
                <div>
                    <h2 class="text-lg font-semibold">
                        Edit Task
                    </h2>
                    <span x-show="saving" class="text-xs text-gray-400">
                        Saving...
                    </span>
                </div>

                <!-- TITLE -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Title
                    </label>
                    <input
                        type="text"
                        class="w-full border rounded px-3 py-2"
                        x-model="selectedTask.title"
                        @change="updateTask({ title: selectedTask.title })"
                    >
                </div>

                <!-- DUE DATE -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Due Date
                    </label>
                    <input
                        type="date"
                        class="border rounded px-3 py-2"
                        x-model="selectedTask.due"
                        @change="updateTask({ due: selectedTask.due })"
                    >
                </div>

                <!-- SUBTASK SECTION -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">
                        Subtask
                    </label>

                    <template x-if="selectedTask.subtasks.length === 0">
                        <div class="text-sm text-gray-400">
                            Tidak ada subtask
                        </div>
                    </template>

                    <template x-for="subtask in selectedTask.subtasks" :key="subtask.id">
                        <div class="flex items-center gap-2 mb-2">
                            <input
                                type="checkbox"
                                :checked="subtask.status === 'done'"
                                @change="toggleSubtask(subtask)"
                            >
                            <span
                                :class="subtask.status === 'done'
                                    ? 'line-through text-gray-400'
                                    : ''"
                                x-text="subtask.content"
                            ></span>
                        </div>
                    </template>
                </div>

                <!-- NOTE -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">
                        Note
                    </label>
                    <textarea
                        class="w-full border rounded px-3 py-2 min-h-[120px]"
                        x-model="selectedTask.note"
                        @change="updateTask({ note: selectedTask.note })"
                    ></textarea>
                </div>

            </div>
        </div>
    </template>
</div>
<script>
function taskApp() {
    return {
        tasks: [],
        categories: [],
        selectedCategory: null,
        selectedTask: null,
        loadingTasks: false,
        saving: false,
        activeTab: 'info', // info | subtasks | note
        newSubtask: '',
        addingSubtask: false,


        async init() {
            await this.loadCategories();
            await this.loadTasks();
        },

        async loadCategories() {
            try {
                const res = await fetch('/categories');
                const data = await res.json();
                this.categories = data.categories ?? [];
            } catch (e) {
                console.error('Gagal load categories', e);
            }
        },

        async loadTasks() {
            this.loadingTasks = true;

            try {
                let url = '/tasks';
                if (this.selectedCategory !== null) {
                    url += `?category_id=${this.selectedCategory}`;
                }

                const res = await fetch(url);
                const data = await res.json();

                this.tasks = data.tasks ?? [];

                if (this.tasks.length > 0) {
                    await this.selectTask(this.tasks[0].id);
                } else {
                    this.selectedTask = null;
                }
            } catch (e) {
                console.error('Gagal load tasks', e);
            } finally {
                this.loadingTasks = false;
            }
        },

        async selectTask(taskId) {
            try {
                const res = await fetch(`/tasks/${taskId}`);
                const data = await res.json();
                this.selectedTask = data.task;
            } catch (e) {
                console.error('Gagal load task detail', e);
            }
        },

        async toggleStatus(taskId) {
            const task = this.tasks.find(t => t.id === taskId);
            if (!task) return;

            // optimistic
            const oldStatus = task.status;
            task.status = task.status === 'yet' ? 'done' : 'yet';

            try {
                await fetch(`/tasks/${taskId}/toggle-status`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    }
                });

                // karena default view = yet
                this.tasks = this.tasks.filter(t => t.status === 'yet');

                if (this.selectedTask?.id === taskId) {
                    this.selectedTask = null;
                }
            } catch (e) {
                task.status = oldStatus;
                alert('Gagal update status');
            }
        },

        async updateTask(payload) {
            if (!this.selectedTask) return;

            this.saving = true;

            try {
                await fetch(`/tasks/${this.selectedTask.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify(payload)
                });
            } catch (e) {
                alert('Gagal menyimpan');
            } finally {
                this.saving = false;
            }
        },

        async addSubtask() {
            if (!this.newSubtask.trim()) return;

            this.addingSubtask = true;

            // optimistic
            const temp = {
                id: Date.now(),
                content: this.newSubtask,
                status: 'yet',
                _temp: true
            };

            this.selectedTask.subtasks.push(temp);
            this.newSubtask = '';

            try {
                const res = await fetch(`/tasks/${this.selectedTask.id}/subtasks`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ content: temp.content })
                });

                const data = await res.json();

                // replace temp with real data
                const idx = this.selectedTask.subtasks.findIndex(s => s._temp);
                if (idx !== -1) {
                    this.selectedTask.subtasks[idx] = data.subtask;
                }
            } catch (e) {
                // rollback
                this.selectedTask.subtasks =
                    this.selectedTask.subtasks.filter(s => !s._temp);
                alert('Gagal menambah subtask');
            } finally {
                this.addingSubtask = false;
            }
        }

        async deleteSubtask(subtask) {
            const original = [...this.selectedTask.subtasks];

            // optimistic remove
            this.selectedTask.subtasks =
                this.selectedTask.subtasks.filter(s => s.id !== subtask.id);

            try {
                await fetch(`/subtasks/${subtask.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    }
                });
            } catch (e) {
                this.selectedTask.subtasks = original;
                alert('Gagal menghapus subtask');
            }
        }





    }
}
</script>


</x-app-layout>

