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
                console.log('load task', this.tasks);
                
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
                // console.log(this.selectedTask.due, 'due date');
                
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

                // console.log(this.tasks);
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
            // console.log('outside clicked');
            this.priorityPopupTaskId = taskId;
        },

        closePriority() {
            // console.log('outside clicked');
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
            // console.log('toggleCategoryMenu called');
            
            this.showCategoryMenu = !this.showCategoryMenu;
        },

        closeCategoryMenu() {
            // console.log('closeCategoryMenu called');

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

        formattedDue() {
            if (!this.selectedTask || !this.selectedTask.due) return '';
            return this.selectedTask.due.split('T')[0];
        },
        
        formatDateForInput(date) {
            console.log('date: ', date);
            
            if (!date) return '';

            // kasus date = "2025-12-23T17:00:00.000000Z"
            return date.split('T')[0];
        }

        // async openCompletedTasks() {
        //     // TUTUP SEMUA MODAL LAIN DULU
        //     this.showCategoryMenu = false;
        //     this.showSortMenu = false;
        //     this.showManageCategories = false;
        //     this.priorityPopupTaskId = null;

        //     // BARU BUKA MODAL COMPLETED
        //     this.showCompletedModal = true;
        //     this.loadingCompleted = true;

        //     try {
        //         const res = await fetch('/tasks/completed');
        //         const data = await res.json();
        //         this.showCompletedModal = true;
        //         this.completedGroups = data.groups ?? {};

        //     } catch {
        //         alert('Gagal load completed tasks');
        //     } finally {
        //         this.loadingCompleted = false;
        //     }
        // },


        // closeCompletedTasks() {
        //     this.showCompletedModal = false;
        //     console.log("CLOSE TEST");
            
        //     // this.loadTasks();
        // },
        // async undoCompleted(task) {
        //     const oldStatus = task.status;

        //     // Optimistic: langsung hilangkan dari modal
        //     const dateKey = Object.keys(this.completedGroups).find(d =>
        //         this.completedGroups[d].some(t => t.id === task.id)
        //     );

        //     if (dateKey) {
        //         this.completedGroups[dateKey] =
        //             this.completedGroups[dateKey].filter(t => t.id !== task.id);

        //         // hapus group kosong
        //         if (this.completedGroups[dateKey].length === 0) {
        //             delete this.completedGroups[dateKey];
        //         }
        //     }

        //     try {
        //         await fetch(`/tasks/${task.id}/toggle-status`, {
        //             method: 'PATCH',
        //             headers: {
        //                 'X-CSRF-TOKEN': document
        //                     .querySelector('meta[name="csrf-token"]')
        //                     .getAttribute('content')
        //             }
        //         });
        //         // RESET SEARCH / CATEGORY supaya task tampak
        //         this.searchQuery = "";
        //         this.selectedCategory = null;

        //         // Tambahkan lagi ke task list kiri
        //         // Reload tasks karena posisi & filtering bisa berubah
        //         await this.loadTasks();
        //         // this.loadTasks();


        //     } catch (e) {
        //         alert('Gagal undo status');

        //         // Rollback
        //         if (dateKey) {
        //             this.completedGroups[dateKey].push(task);
        //         }
        //     }
        // },


    }))
})