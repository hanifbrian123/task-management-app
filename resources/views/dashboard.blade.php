<x-app-layout>

<div 
    x-data="taskApp" 
    x-init="init()"
    class="grid grid-cols-5 h-[calc(100vh-4rem)]"
>
    <!-- PANEL KIRI -->
    <div class="col-span-2 border-r bg-gray-50 p-4 overflow-y-auto">


        <!-- CATEGORY -->
        <div class="relative flex items-center mb-4">
            <!-- LEFT: CATEGORY CHIPS -->
            <div class="flex gap-2 flex-wrap">
                <!-- ALL -->
                <button
                    class="px-3 py-1 rounded-full text-sm"
                    :class="selectedCategory === null
                        ? 'bg-indigo-600 text-white'
                        : 'bg-gray-200 text-gray-700'"
                    @click="selectedCategory = null; loadTasks()"
                >
                    All
                </button>

                <!-- CATEGORIES -->
                <template x-for="cat in categories" :key="cat.id">
                    <button
                        class="px-3 py-1 rounded-full text-sm"
                        :class="selectedCategory === cat.id
                            ? 'bg-indigo-600 text-white'
                            : 'bg-gray-200 text-gray-700'"
                        @click="selectedCategory = cat.id; loadTasks()"
                        x-text="cat.name"
                    ></button>
                </template>
            </div>

            <!-- RIGHT: THREE DOTS -->
            <div class="ml-auto relative">
                <!-- BUTTON ⋯ -->
                <button
                    type="button"
                    class="text-gray-500 hover:text-gray-700 px-2"
                    @click="showCategoryMenu = !showCategoryMenu; showSortMenu = false"
                >
                    ⋯
                </button>

                <!-- MAIN MENU -->
                <div
                    x-show="showCategoryMenu"
                    x-transition
                    @click.outside="showCategoryMenu = false; showSortMenu = false"
                    class="absolute right-0 top-8 z-30 w-56 bg-white border rounded-xl shadow-lg py-2"
                >
                    <!-- MANAGE -->
                    <button
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100"
                        @click="openManageCategories"
                    >
                        Manage Categories
                    </button>

                    <!-- SORT BY -->
                    <button
                        class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 flex justify-between"
                        @click.stop="showSortMenu = !showSortMenu"
                    >
                        Sort by
                        <span>›</span>
                    </button>
                </div>

                <!-- SORT BY SUBMENU (SEPARATE, SIBLING) -->
                <div
                    x-show="showSortMenu"
                    x-transition
                    @click.outside="showSortMenu = false"
                    class="absolute right-full top-8 mr-2 z-40 w-64 bg-white border rounded-xl shadow-lg p-3"
                >
                    <p class="text-sm font-semibold mb-2">
                        Sort tasks by
                    </p>

                    <label class="flex items-center gap-2 text-sm mb-2 cursor-pointer">
                        <input
                            type="radio"
                            name="sort"
                            value="due"
                            :checked="sortMode === 'due'"
                            @change="changeSort('due')"
                        >
                        Due date
                    </label>

                    <label class="flex items-center gap-2 text-sm mb-2 cursor-pointer">
                        <input
                            type="radio"
                            name="sort"
                            value="created"
                            :checked="sortMode === 'created'"
                            @change="changeSort('created')"
                        >
                        Task created
                    </label>

                    <label class="flex items-center gap-2 text-sm cursor-pointer">
                        <input
                            type="radio"
                            name="sort"
                            value="manual"
                            :checked="sortMode === 'manual'"
                            @change="changeSort('manual')"
                        >
                        Manual (long press to sort)
                    </label>
                </div>
            </div>


        </div>



        <div x-show="showManageCategories">
            <div
                class="fixed inset-0 bg-black/30 z-40 flex items-center justify-center"
            >
                <div
                    class="bg-white rounded-xl shadow-lg w-96 p-4"
                    @click.outside="closeManageCategories"
                >
                    <div class="flex justify-between items-center mb-3">
                        <h2 class="font-semibold">Manage Categories</h2>
                        <button @click="closeManageCategories">✕</button>
                    </div>

                    <!-- ADD -->
                    <div class="flex gap-2 mb-3">
                        <input
                            class="flex-1 border rounded px-2 py-1 text-sm"
                            placeholder="New category..."
                            x-model="newCategoryName"
                            @keydown.enter="createCategory"
                        >
                        <button
                            class="px-3 py-1 bg-indigo-600 text-white rounded text-sm"
                            @click="createCategory"
                        >
                            Add
                        </button>
                    </div>

                    <!-- SEARCH -->
                    <input
                        class="w-full border rounded px-2 py-1 text-sm mb-3"
                        placeholder="Search"
                        x-model="categorySearch"
                    >

                    <div class="space-y-2 max-h-60 overflow-auto">
                        <template x-for="cat in filteredCategories()" :key="cat.id">
                            <div class="flex items-center gap-2 bg-gray-100 rounded px-3 py-2">
                                
                                <!-- VIEW -->
                                <template x-if="editingCategoryId !== cat.id">
                                    <span
                                        class="flex-1 cursor-pointer"
                                        @click="startEditCategory(cat)"
                                        x-text="cat.name"
                                    ></span>
                                </template>

                                <!-- EDIT -->
                                <template x-if="editingCategoryId === cat.id">
                                    <input
                                        :id="`edit-cat-${cat.id}`"
                                        class="flex-1 border rounded px-2 py-1 text-sm"
                                        x-model="cat.name"
                                        @keydown.enter="saveCategory(cat)"
                                        @blur="saveCategory(cat)"
                                    >
                                </template>

                                <!-- ACTIONS -->
                                <button
                                    class="text-gray-500 hover:text-indigo-600"
                                    @click="startEditCategory(cat)"
                                >
                                    ✎
                                </button>

                                <button
                                    class="text-gray-500 hover:text-red-600"
                                    @click="deleteCategory(cat)"
                                >
                                    ✕
                                </button>
                            </div>
                        </template>
                    </div>

                                    </div>
            </div>
        </div>




        <!-- LOADING -->
        <div x-show="loadingTasks" class="text-sm text-gray-400">
            Loading tasks...
        </div>
        <input
            type="text"
            placeholder="Search tasks..."
            class="w-full mb-3 border rounded px-3 py-2 text-sm"
            x-model="searchQuery"
            @input="
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => loadTasks(), 300);
            "
        >

        <!-- TASK LIST -->
        <template x-if="!loadingTasks && tasks.length === 0">
            <div class="text-sm text-gray-400">
                Tidak ada task
            </div>
        </template>

            <template x-for="task in tasks" :key="task.id">
                <div
                    class="relative p-3 mb-2 rounded flex items-center justify-between cursor-pointer"
                    :class="
                        selectedTask && selectedTask.id === task.id
                            ? 'bg-gray-200'
                            : 'bg-white hover:bg-gray-100'
                    "
                    @click="selectTask(task.id)"
                >

                    <!-- LEFT: DRAG HANDLE + CHECKBOX + TITLE -->
                    <div class="flex items-center gap-3">

                        <!-- DRAG HANDLE (ONLY THIS CAN DRAG, ONLY IN MANUAL MODE) -->
                        <span
                            class="select-none text-gray-400"
                            :class="sortMode === 'manual'
                                ? 'cursor-grab'
                                : 'cursor-not-allowed opacity-40'"
                            :draggable="sortMode === 'manual'"
                            @dragstart="sortMode === 'manual' && onDragStart(task.id)"
                            @dragover.prevent="sortMode === 'manual'"
                            @drop="sortMode === 'manual' && onDrop(task.id)"
                            @click.stop
                        >
                            ☰
                        </span>


                        <!-- CHECKBOX -->
                        <input
                            type="checkbox"
                            @click.stop="toggleStatus(task.id)"
                            :checked="task.status === 'done'"
                        >

                        <!-- TITLE -->
                        <span
                            x-text="task.title"
                            :class="task.status === 'done'
                                ? 'line-through text-gray-400'
                                : ''"
                        ></span>
                    </div>


                <!-- RIGHT: PRIORITY -->
                <div
                    class="w-7 h-7 rounded-full text-xs flex items-center justify-center font-semibold"
                    :class="task.priority
                        ? 'text-white ' + priorityColor(task.priority)
                        : 'border border-gray-400 text-gray-500'"
                    @click.stop="openPriority(task.id)"
                >
                    <span x-text="task.priority ?? '–'"></span>

                    <!-- POPUP -->
                    <template x-if="priorityPopupTaskId === task.id">
                        <div
                            class="absolute z-20 top-full right-0 mt-2 bg-white border rounded shadow p-3"
                            @click.outside="closePriority"
                        >
                            <!-- NUMBERS -->
                            <div class="flex gap-2 mb-2">
                                <template x-for="n in [1,2,3,4,5]" :key="n">
                                    <button
                                        class="w-8 h-8 rounded-full text-white text-sm"
                                        :class="priorityColor(n)"
                                        @click="setPriority(task, n)"
                                        x-text="n"
                                    ></button>
                                </template>
                            </div>

                            <!-- CLEAR -->
                            <button
                                class="w-full text-sm text-gray-600 hover:text-red-600 border-t pt-2"
                                @click="setPriority(task, null)"
                            >
                                Clear
                            </button>
                        </div>
                    </template>

                </div>
            </div>
        </template>

        <!-- ADD TASK -->
        <template x-if="addingTask">
            <input
                x-ref="newTaskInput"
                class="w-full border rounded px-3 py-2 mt-2 text-sm"
                placeholder="New task..."
                x-model="newTaskTitle"
                @keydown.enter="saveNewTask"
                @blur="saveNewTask"
            >
        </template>

        <button
            class="text-sm text-indigo-600 mt-2"
            @click="startAddTask"
        >
            + Add Task
        </button>


        <div class="mt-6 pt-4 border-t">
            <button
                class="text-sm text-gray-500 hover:text-indigo-600 flex items-center gap-2"
                @click="openCompletedTasks"
            >
                ✓ Check all completed tasks
            </button>
        </div>

        <template x-if="showCompletedModal">
            <div class="fixed inset-0 bg-black/40 z-50 flex justify-center items-center">
                <div
                    class="bg-white w-full max-w-lg max-h-[80vh] rounded-xl shadow-lg p-4 overflow-y-auto"
                    @click.outside="closeCompletedTasks"
                >
                    <!-- HEADER -->
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="font-semibold">Completed Tasks</h2>
                        <button @click="closeCompletedTasks">✕</button>
                    </div>

                    <!-- LOADING -->
                    <template x-if="loadingCompleted">
                        <p class="text-sm text-gray-400">Loading...</p>
                    </template>

                    <!-- GROUPED LIST -->
                    <template x-for="[date, tasks] in Object.entries(completedGroups)" :key="date">
                        <div class="mb-4">
                            <h3 class="text-sm font-semibold text-gray-500 mb-2">
                                Completed Time — <span x-text="date"></span>
                            </h3>

                            <div class="space-y-2">
                                <template x-for="task in tasks" :key="task.id">
                                    <div class="flex items-center gap-3 bg-gray-100 rounded px-3 py-2">
                                        <input type="checkbox" checked disabled>

                                        <span
                                            class="line-through text-gray-500 text-sm"
                                            x-text="task.title"
                                        ></span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>

                    <!-- EMPTY -->
                    <template x-if="!loadingCompleted && Object.keys(completedGroups).length === 0">
                        <p class="text-sm text-gray-400">
                            No completed tasks yet
                        </p>
                    </template>
                </div>
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
                    <label class="block text-sm font-medium mb-1">
                        Due Date
                    </label>
                    <p
                        class="text-xs mt-1"
                        :class="selectedTask.due ? 'text-red-500' : 'text-gray-400'"
                    >
                        <span x-show="selectedTask.due">
                            Due set
                        </span>
                        <span x-show="!selectedTask.due">
                            No due date
                        </span>
                    </p>

                    <input
                        type="date"
                        x-model="selectedTask.due"
                        @change="updateTask({ due: selectedTask.due })"
                        class="w-full border rounded px-3 py-2 text-sm"
                        :class="selectedTask.due
                            ? 'border-red-500 text-red-600'
                            : 'border-gray-300 text-gray-700'"
                    >
                </div>



                <!-- CATEGORY SECTION -->
                <div>
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
                </div>


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
                            <!-- DRAG HANDLE (ONLY HERE) -->
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


                    <!-- ADD -->
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
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('taskApp', () => ({

        /* =====================
           STATE
        ===================== */
        tasks: [],
        categories: [],
        selectedCategory: null,
        selectedTask: null,
        loadingTasks: false,
        saving: false,

        newSubtask: '',
        addingSubtask: false,

        addingSubtask: false,
        newSubtaskText: '',
        editingSubtaskId: null,

        addingTask: false,
        newTaskTitle: '',

        priorityPopupTaskId: null,


        showCategoryMenu: false,
        categorySearch: '',
        categorySort: 'name',

        showManageCategories: false,
        newCategoryName: '',

        editingCategoryId: null,

        searchQuery: '',
        searchTimeout: null,


        draggingTaskId: null,

        draggingSubtaskId: null,

        showSortMenu: false,

        sortMode: 'manual', 
        // 'due' | 'created' | 'manual'

        showCompletedModal: false,
        completedGroups: {},
        loadingCompleted: false,
        /* =====================
           INIT
        ===================== */
        async init() {
            await this.loadCategories();
            await this.loadTasks();
        },

        /* =====================
           LOADERS
        ===================== */
        async loadCategories() {
            try {
                const res = await fetch('/categories');
                const data = await res.json();
                this.categories = data.categories ?? [];
            } catch (e) {
                console.error('Load categories failed', e);
            }
        },

        async loadTasks() {
            this.loadingTasks = true;

            try {
                let url = '/tasks?';

                if (this.selectedCategory !== null) {
                    url += `category_id=${this.selectedCategory}&`;
                }

                if (this.searchQuery.trim()) {
                    url += `q=${encodeURIComponent(this.searchQuery)}&`;
                }

                url += `sort=${this.sortMode}`;
                
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
                console.error('Load task detail failed', e);
            }
        },

        /* =====================
           TASK
        ===================== */
        async toggleStatus(taskId) {
            const task = this.tasks.find(t => t.id === taskId);
            if (!task) return;

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

                this.tasks = this.tasks.filter(t => t.status === 'yet');

                if (this.selectedTask?.id === taskId) {
                    this.selectedTask = null;
                }
            } catch {
                task.status = oldStatus;
                alert('Gagal update status task');
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
            } catch {
                alert('Gagal menyimpan task');
            } finally {
                this.saving = false;
            }
        },

        /* =====================
           SUBTASK
        ===================== */
        async addSubtask() {
            if (!this.newSubtask.trim() || !this.selectedTask) return;

            this.addingSubtask = true;

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
                const idx = this.selectedTask.subtasks.findIndex(s => s._temp);
                if (idx !== -1) {
                    this.selectedTask.subtasks[idx] = data.subtask;
                }
            } catch {
                this.selectedTask.subtasks =
                    this.selectedTask.subtasks.filter(s => !s._temp);
                alert('Gagal menambah subtask');
            } finally {
                this.addingSubtask = false;
            }
        },

        async deleteSubtask(subtask) {
            if (!this.selectedTask) return;

            const original = [...this.selectedTask.subtasks];
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
            } catch {
                this.selectedTask.subtasks = original;
                alert('Gagal menghapus subtask');
            }
        },

        async toggleSubtask(subtask) {
            const oldStatus = subtask.status;
            subtask.status = oldStatus === 'yet' ? 'done' : 'yet';

            try {
                await fetch(`/subtasks/${subtask.id}/toggle`, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    }
                });
            } catch {
                subtask.status = oldStatus;
                alert('Gagal update subtask');
            }
        },

        /* =====================
           CATEGORY
        ===================== */
        hasCategory(catId) {
            if (!this.selectedTask) return false;
            return this.selectedTask.categories.some(c => c.id === catId);
        },

        async toggleCategory(category) {
            if (!this.selectedTask) return;

            const exists = this.hasCategory(category.id);

            // optimistic
            if (exists) {
                this.selectedTask.categories =
                    this.selectedTask.categories.filter(c => c.id !== category.id);
            } else {
                this.selectedTask.categories.push(category);
            }

            try {
                if (exists) {
                    await fetch(`/tasks/${this.selectedTask.id}/categories/${category.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        }
                    });
                } else {
                    await fetch(`/tasks/${this.selectedTask.id}/categories`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({ category_id: category.id })
                    });
                }
            } catch {
                alert('Gagal update category');
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
                const idx = this.selectedTask.subtasks.findIndex(s => s._temp);
                if (idx !== -1) {
                    this.selectedTask.subtasks[idx] = data.subtask;
                }
            } catch {
                this.selectedTask.subtasks =
                    this.selectedTask.subtasks.filter(s => !s._temp);
                alert('Gagal menambah subtask');
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

            try {
                await fetch(`/subtasks/${subtask.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ content: subtask.content })
                });
            } catch {
                alert('Gagal update subtask');
                await this.selectTask(this.selectedTask.id);
            }
        },


        startAddTask() {
            this.addingTask = true;
            this.$nextTick(() => {
                this.$refs.newTaskInput?.focus();
            });
        },

        async saveNewTask() {
            if (!this.newTaskTitle.trim()) {
                this.addingTask = false;
                return;
            }

            const temp = {
                id: Date.now(),
                title: this.newTaskTitle,
                status: 'yet',
                priority: 3,
                _temp: true
            };

            // optimistic insert
            this.tasks.unshift(temp);
            this.newTaskTitle = '';
            this.addingTask = false;

            try {
                const res = await fetch('/tasks', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ title: temp.title })
                });

                const data = await res.json();

                // replace temp
                const idx = this.tasks.findIndex(t => t._temp);
                if (idx !== -1) {
                    this.tasks[idx] = data.task;
                    await this.selectTask(data.task.id);
                }
            } catch {
                this.tasks = this.tasks.filter(t => !t._temp);
                alert('Gagal menambah task');
            }
        },

        openPriority(taskId) {
            this.priorityPopupTaskId = taskId;
        },

        closePriority() {
            this.priorityPopupTaskId = null;
        },

        async setPriority(task, value) {
            const old = task.priority;
            task.priority = value;
            this.closePriority();

            try {
                await fetch(`/tasks/${task.id}/priority`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ priority: value })
                });
            } catch {
                task.priority = old;
                alert('Gagal update priority');
            }
        },

        

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


        toggleCategoryMenu() {
            this.showCategoryMenu = !this.showCategoryMenu;
        },

        closeCategoryMenu() {
            this.showCategoryMenu = false;
        },

        filteredCategories() {
            let cats = [...this.categories];

            if (this.categorySearch.trim()) {
                cats = cats.filter(c =>
                    c.name.toLowerCase().includes(
                        this.categorySearch.toLowerCase()
                    )
                );
            }

            if (this.categorySort === 'name') {
                cats.sort((a, b) => a.name.localeCompare(b.name));
            }

            if (this.categorySort === 'created') {
                cats.sort((a, b) =>
                    new Date(a.created_at) - new Date(b.created_at)
                );
            }

            return cats;
        },

        openManageCategories() {
            this.showManageCategories = true;
            this.showCategoryMenu = false;
        },

        closeManageCategories() {
            this.showManageCategories = false;
        },

        async createCategory() {
            if (!this.newCategoryName.trim()) return;

            const temp = {
                id: Date.now(),
                name: this.newCategoryName,
                _temp: true
            };

            this.categories.push(temp);
            this.newCategoryName = '';

            try {
                const res = await fetch('/categories', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ name: temp.name })
                });

                const data = await res.json();
                const idx = this.categories.findIndex(c => c._temp);
                if (idx !== -1) {
                    this.categories[idx] = data.category;
                }
            } catch {
                this.categories = this.categories.filter(c => !c._temp);
                alert('Gagal menambah category');
            }
        },

        startEditCategory(cat) {
            this.editingCategoryId = cat.id;
            this.$nextTick(() => {
                document.getElementById(`edit-cat-${cat.id}`)?.focus();
            });
        },

        async saveCategory(cat) {
            this.editingCategoryId = null;

            try {
                await fetch(`/categories/${cat.id}`, {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({ name: cat.name })
                });
            } catch {
                alert('Gagal rename category');
                await this.loadCategories(); // rollback
            }
        },

        async deleteCategory(cat) {
            if (!confirm(`Delete category "${cat.name}"?`)) return;

            const original = [...this.categories];
            this.categories = this.categories.filter(c => c.id !== cat.id);

            try {
                await fetch(`/categories/${cat.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    }
                });

                // refresh tasks (category filter bisa berubah)
                await this.loadTasks();
            } catch {
                this.categories = original;
                alert('Gagal menghapus category');
            }
        },


        onDragStart(taskId) {
            if (this.sortMode !== 'manual') return;
            this.draggingTaskId = taskId;
        },

        onDragOver(event) {
            event.preventDefault();
        },


        async onDrop(targetTaskId) {
            if (this.draggingTaskId === null) return;

            const fromIndex = this.tasks.findIndex(t => t.id === this.draggingTaskId);
            const toIndex = this.tasks.findIndex(t => t.id === targetTaskId);

            if (fromIndex === -1 || toIndex === -1) return;

            // reorder locally
            const moved = this.tasks.splice(fromIndex, 1)[0];
            this.tasks.splice(toIndex, 0, moved);

            this.draggingTaskId = null;

            // persist
            try {
                await fetch('/tasks/reorder', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        order: this.tasks.map(t => t.id)
                    })
                });
            } catch {
                alert('Gagal menyimpan urutan');
                await this.loadTasks();
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
                await fetch(`/tasks/${this.selectedTask.id}/subtasks/reorder`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document
                            .querySelector('meta[name="csrf-token"]')
                            .getAttribute('content')
                    },
                    body: JSON.stringify({
                        order: list.map(s => s.id)
                    })
                });
            } catch {
                alert('Gagal menyimpan urutan subtask');
                await this.selectTask(this.selectedTask.id);
            }
        },

        openSortMenu() {
            this.showSortMenu = true;
        },

        closeSortMenu() {
            this.showSortMenu = false;
        },

        async changeSort(mode) {
            this.sortMode = mode;
            this.showSortMenu = false;
            await this.loadTasks();
        },
        async openCompletedTasks() {
            this.showCompletedModal = true;
            this.loadingCompleted = true;

            try {
                const res = await fetch('/tasks/completed');
                const data = await res.json();
                this.completedGroups = data.groups ?? {};
            } catch {
                alert('Gagal load completed tasks');
            } finally {
                this.loadingCompleted = false;
            }
        },

        closeCompletedTasks() {
            this.showCompletedModal = false;
        },



    }))
})
</script>




</x-app-layout>

