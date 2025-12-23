<x-app-layout>

<div 
    x-data="calendarPage()" 
    x-init="init()"
    class="grid grid-cols-2 h-[calc(100vh-4rem)]"
> 

    <!-- LEFT: CALENDAR -->
    <div class="col-span-1 border-r p-6 bg-gray-50 overflow-y-auto">

        <!-- MONTH HEADER -->
        <div class="flex justify-between items-center mb-4">
            <button @click="prevMonth" class="text-xl">‹</button>
            <h2 class="font-semibold text-lg" x-text="monthLabel"></h2>
            <button @click="nextMonth" class="text-xl">›</button>
        </div>

        <!-- CALENDAR GRID -->
        <div class="grid grid-cols-7 gap-2 text-center text-sm font-semibold mb-2">
            <span>Sun</span><span>Mon</span><span>Tue</span>
            <span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
        </div>

        <div class="grid grid-cols-7 gap-2">
            <template x-for="day in days" :key="day.date">
                <div
                    class="aspect-square rounded flex items-center justify-center relative cursor-pointer"
                    :class="{
                        'bg-indigo-600 text-white': day.date === selectedDate,
                        'hover:bg-gray-200': day.date !== selectedDate
                    }"
                    @click="selectDate(day.date)"
                >
                    <span x-text="day.day"></span>

                    <!-- DOT IF HAS TASK -->
                    <template x-if="taskDates.includes(day.date)">
                        <div class="absolute bottom-2 w-2 h-2 rounded-full bg-indigo-600"></div>
                    </template>
                </div>
            </template>
        </div>

    </div>



    <!-- RIGHT: TASKS / DETAIL -->
    <div class="col-span-1 border-l p-4 overflow-y-auto">

        <!-- TASK LIST (shown when no detail open) -->
        <template x-if="!selectedTask">
            <div class="p-4">
                <template x-if="selectedDate">
                    <h2 class="font-semibold mb-4">Tasks for <span x-text="selectedDate"></span></h2>
                </template>

                <template x-if="!selectedDate">
                    <h2 class="font-semibold mb-2">Select a date</h2>
                    <p class="text-sm text-gray-400 mb-4">Pick a date on the left to view tasks for that day.</p>
                </template>

                <template x-if="loadingTasks">
                    <p class="text-sm text-gray-400">Loading...</p>
                </template>

                <template x-if="selectedDate && !loadingTasks && tasks.length === 0">
                    <p class="text-sm text-gray-400">No tasks</p>
                </template>

                <template x-for="task in tasks" :key="task.id">
                    <div
                        @click="selectTask(task.id)"
                        class="relative p-3 mb-2 rounded flex items-center justify-between cursor-pointer bg-white hover:bg-gray-100"
                    >
                        <div class="flex items-center gap-3">
                            <!-- TITLE (non-editable here) -->
                            <span class="text-sm" x-text="task.title"></span>
                        </div>

                        <!-- PRIORITY (display only, no popup) -->
                        <div
                            class="w-7 h-7 rounded-full text-xs flex items-center justify-center font-semibold"
                            :class="task.priority ? 'text-white ' + priorityColor(task.priority) : 'border border-gray-400 text-gray-500'"
                        >
                            <span x-text="task.priority ?? '–'"></span>
                        </div>
                    </div>
                </template>
            </div>
        </template>

        <!-- DETAIL -->
        <template x-if="selectedTask">
            <div class="p-6 bg-gray-100">
                <div class="bg-white rounded-lg shadow p-6 max-w-3xl space-y-6">

                    <!-- HEADER -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <button @click="selectedTask = null" class="text-sm text-gray-500 hover:text-gray-700">← Back</button>
                            <h2 class="text-lg font-semibold">Edit Task</h2>
                        </div>
                        <span x-show="saving" class="text-xs text-gray-400">Saving...</span>
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
                    <label class="block text-sm font-medium mb-1">
                        Due Date
                    </label>

                    <p
                        class="text-xs mt-1"
                        :class="selectedTask.due ? 'text-red-500' : 'text-gray-400'"
                    >
                        <span x-show="selectedTask.due">Due set</span>
                        <span x-show="!selectedTask.due">No due date</span>
                    </p>

                    <input
                        type="date"
                        x-model="selectedTask.due"
                        @change="updateTask({ due: selectedTask.due })"
                        x-init="$watch('selectedTask', value => {
                            if (value && value.due) {
                                // console.log('due date changed:', value.due.split('T'));
                                selectedTask.due = value.due.split('T')[0];
                            }
                        })"
                        class="w-full border rounded px-3 py-2 text-sm"
                    >
                </div>

                <!-- CATEGORY -->
                {{-- <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">
                        Category
                    </label>

                    <div class="flex gap-2 flex-wrap">
                        <template x-for="cat in categories" :key="cat.id">
                            <button
                                class="px-3 py-1 rounded text-sm border"
                                :class="hasCategory(cat.id)
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-gray-100 text-gray-600'"
                                @click="toggleCategory(cat)"
                                x-text="cat.name"
                            ></button>
                        </template>
                    </div>
                </div> --}}

                <!-- SUBTASK SECTION -->
                <div>
                    <label class="block text-sm font-medium text-gray-600 mb-2">
                        Subtask
                    </label>

                    <!-- SUBTASK LIST -->
                    <template x-for="subtask in selectedTask.subtasks" :key="subtask.id">
                        <div
                            class="flex items-center gap-2 mb-2"
                            @dragover.prevent
                            @drop="onSubtaskDrop(subtask.id)"
                        >
                            <!-- DRAG HANDLE -->
                            <span
                                class="cursor-grab text-gray-400 select-none"
                                draggable="true"
                                @dragstart="onSubtaskDragStart(subtask.id)"
                            >
                                ☰
                            </span>

                            <!-- CHECKBOX -->
                            <input
                                type="checkbox"
                                :checked="subtask.status === 'done'"
                                @change="toggleSubtask(subtask)"
                            >

                            <!-- CONTENT -->
                            <div class="flex-1">
                                <!-- VIEW -->
                                <template x-if="editingSubtaskId !== subtask.id">
                                    <span
                                        class="cursor-pointer block"
                                        :class="subtask.status === 'done'
                                            ? 'line-through text-gray-400'
                                            : ''"
                                        @click="startEditSubtask(subtask)"
                                        x-text="subtask.content"
                                    ></span>
                                </template>

                                <!-- EDIT -->
                                <template x-if="editingSubtaskId === subtask.id">
                                    <input
                                        :id="`edit-subtask-${subtask.id}`"
                                        class="w-full border rounded px-2 py-1 text-sm"
                                        x-model="subtask.content"
                                        @keydown.enter.prevent="saveSubtask(subtask)"
                                        @keydown.escape="editingSubtaskId = null"
                                        @blur="saveSubtask(subtask)"
                                    >
                                </template>
                            </div>

                            <!-- DELETE -->
                            <button
                                class="text-red-500 text-sm px-1"
                                @click="deleteSubtask(subtask)"
                            >
                                ✕
                            </button>
                        </div>
                    </template>

                    <!-- ADD SUBTASK -->
                    <template x-if="addingSubtask">
                        <input
                            x-ref="newSubtaskInput"
                            class="w-full border rounded px-2 py-1 mt-2 text-sm"
                            placeholder="New subtask..."
                            x-model="newSubtaskText"
                            @keydown.enter="saveNewSubtask"
                            @blur="saveNewSubtask"
                        >
                    </template>

                    <button
                        class="text-sm text-indigo-600 mt-2"
                        @click="startAddSubtask"
                    >
                        + Add Subtask
                    </button>
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

</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('calendarPage', () => ({

        month: new Date().getMonth(),
        year: new Date().getFullYear(),
        selectedDate: null,

        days: [],
        taskDates: [],
        tasks: [],
        loadingTasks: false,

        // Task/detail related state
        categories: [],
        selectedTask: null,
        saving: false,

        addingSubtask: false,
        newSubtaskText: '',
        editingSubtaskId: null,
        draggingSubtaskId: null,

        async init() {
            this.buildCalendar();
            await this.loadTaskDates();
            // tasks are loaded only after a date is selected
            await this.loadCategories();
        },

        monthLabel() {
            return `${this.year} - ${this.month + 1}`;
        },

        buildCalendar() {
            const first = new Date(this.year, this.month, 1);
            const last = new Date(this.year, this.month + 1, 0);

            const result = [];

            for (let d = 1; d <= last.getDate(); d++) {
                const date = new Date(this.year, this.month, d)
                    .toISOString().slice(0, 10);

                result.push({ day: d, date });
            }

            this.days = result;
        },

        async loadCategories() {
            try {
                const res = await fetch('/categories');
                const data = await res.json();
                this.categories = data.categories ?? [];
            } catch (e) {
                console.error('Load categories failed', e);
            }
        },

        async loadTaskDates() {
            try {
                const res = await fetch('/tasks/dates', { headers: { 'Accept': 'application/json' } });
                if (!res.ok) {
                    console.warn('Failed to load task dates', res.status);
                    this.taskDates = [];
                    return;
                }
                const data = await res.json();
                this.taskDates = data.dates ?? [];
            } catch (e) {
                console.warn('Error loading task dates', e);
                this.taskDates = [];
            }
        },

        async loadTasksForSelectedDate() {
            if (!this.selectedDate) {
                // nothing selected yet
                this.tasks = [];
                return;
            }

            this.loadingTasks = true;

            const res = await fetch(`/tasks/by-date?date=${this.selectedDate}`, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) {
                console.warn('Failed to load tasks for date', res.status);
                this.tasks = [];
                this.loadingTasks = false;
                return;
            }
            const data = await res.json();
            this.tasks = data.tasks ?? [];

            this.loadingTasks = false;
        },

        selectDate(date) {
            this.selectedDate = date;
            this.selectedTask = null;
            this.loadTasksForSelectedDate();
        },

        async selectTask(taskId) {
            try {
                const res = await fetch(`/tasks/${taskId}`);
                const data = await res.json();
                this.selectedTask = data.task;
                
                // Format due date to YYYY-MM-DD (remove time if present)
                if (this.selectedTask.due) {
                    
                    this.selectedTask.due = this.selectedTask.due.split('T')[0];
                    console.log(this.selectedTask.due);
                }
            } catch (e) {
                console.error('Load task detail failed', e);
            }
        },

        async updateTask(payload) {
            if (!this.selectedTask) return;

            this.saving = true;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                await fetch(`/tasks/${this.selectedTask.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify(payload)
                });

                // optimistic merge
                Object.assign(this.selectedTask, payload);
            } catch (e) {
                alert('Gagal menyimpan task');
                console.error(e);
            } finally {
                this.saving = false;
            }
        },

        startEditSubtask(subtask) {
            this.editingSubtaskId = subtask.id;
            this.$nextTick(() => {
                document.getElementById(`edit-subtask-${subtask.id}`)?.focus();
            });
        },

        async saveSubtask(subtask) {
            this.editingSubtaskId = null;
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                await fetch(`/subtasks/${subtask.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ content: subtask.content })
                });
            } catch (e) {
                alert('Gagal update subtask');
                console.error(e);
                await this.selectTask(this.selectedTask.id);
            }
        },

        startAddSubtask() {
            this.addingSubtask = true;
            this.$nextTick(() => {
                this.$refs.newSubtaskInput?.focus();
            });
        },

        async saveNewSubtask() {
            if (!this.newSubtaskText.trim() || !this.selectedTask) return;

            const temp = {
                id: Date.now(),
                content: this.newSubtaskText,
                status: 'yet',
                _temp: true
            };

            this.selectedTask.subtasks.push(temp);
            this.newSubtaskText = '';
            this.addingSubtask = false;

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const res = await fetch(`/tasks/${this.selectedTask.id}/subtasks`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ content: temp.content })
                });

                const data = await res.json();
                const idx = this.selectedTask.subtasks.findIndex(s => s._temp);
                if (idx !== -1) {
                    this.selectedTask.subtasks.splice(idx, 1, data.subtask);
                }
            } catch (e) {
                this.selectedTask.subtasks = this.selectedTask.subtasks.filter(s => !s._temp);
                alert('Gagal menambah subtask');
                console.error(e);
            }
        },

        async deleteSubtask(subtask) {
            if (!this.selectedTask) return;

            const original = [...this.selectedTask.subtasks];
            this.selectedTask.subtasks = this.selectedTask.subtasks.filter(s => s.id !== subtask.id);

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                await fetch(`/subtasks/${subtask.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                });
            } catch (e) {
                this.selectedTask.subtasks = original;
                alert('Gagal menghapus subtask');
                console.error(e);
            }
        },

        async toggleSubtask(subtask) {
            const oldStatus = subtask.status;
            subtask.status = oldStatus === 'yet' ? 'done' : 'yet';

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                await fetch(`/subtasks/${subtask.id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': token
                    }
                });
            } catch (e) {
                subtask.status = oldStatus;
                alert('Gagal update subtask');
                console.error(e);
            }
        },

        hasCategory(catId) {
            if (!this.selectedTask) return false;
            return this.selectedTask.categories.some(c => c.id === catId);
        },

        async toggleCategory(category) {
            if (!this.selectedTask) return;

            const exists = this.hasCategory(category.id);

            // optimistic
            if (exists) {
                this.selectedTask.categories = this.selectedTask.categories.filter(c => c.id !== category.id);
            } else {
                this.selectedTask.categories.push(category);
            }

            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                if (exists) {
                    await fetch(`/tasks/${this.selectedTask.id}/categories/${category.id}`, {
                        method: 'DELETE',
                        headers: { 'X-CSRF-TOKEN': token }
                    });
                } else {
                    await fetch(`/tasks/${this.selectedTask.id}/categories`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token
                        },
                        body: JSON.stringify({ id: category.id })
                    });
                }
            } catch (e) {
                alert('Gagal update category');
                console.error(e);
                await this.selectTask(this.selectedTask.id);
            }
        },

        onSubtaskDragStart(id) {
            this.draggingSubtaskId = id;
        },

        onSubtaskDragOver(e) {
            e.preventDefault();
        },

        async onSubtaskDrop(targetId) {
            if (!this.selectedTask || this.draggingSubtaskId === null) return;

            const list = this.selectedTask.subtasks;
            const from = list.findIndex(s => s.id === this.draggingSubtaskId);
            const to = list.findIndex(s => s.id === targetId);

            if (from === -1 || to === -1) return;

            const moved = list.splice(from, 1)[0];
            list.splice(to, 0, moved);

            this.draggingSubtaskId = null;

            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                await fetch(`/tasks/${this.selectedTask.id}/subtasks/reorder`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token
                    },
                    body: JSON.stringify({ order: this.selectedTask.subtasks.map(s => s.id) })
                });
            } catch (e) {
                alert('Gagal menyimpan urutan subtask');
                console.error(e);
                await this.selectTask(this.selectedTask.id);
            }
        },

        // minimal priority colour helper (display only)
        priorityColor(level) {
            switch (level) {
                case 1: return 'bg-red-400';
                case 2: return 'bg-yellow-400';
                case 3: return 'bg-purple-400';
                case 4: return 'bg-blue-400';
                case 5: return 'bg-green-400';
                default: return 'bg-gray-300 text-gray-600';
            }
        },


        prevMonth() {
            this.month--;
            if (this.month < 0) {
                this.month = 11;
                this.year--;
            }
            this.buildCalendar();
        },

        nextMonth() {
            this.month++;
            if (this.month > 11) {
                this.month = 0;
                this.year++;
            }
            this.buildCalendar();
        }

    }))
});
</script>
<script src="{{ asset('js/task-app.js') }}"></script>

</x-app-layout>
