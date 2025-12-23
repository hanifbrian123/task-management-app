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

        <!-- DRAG HANDLE (manual mode only) -->
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

        <!-- POPUP PRIORITY -->
        <template x-if="priorityPopupTaskId === task.id">
            <div
                class="absolute z-20 top-full right-0 mt-2 bg-white border rounded shadow p-3"
                @click.outside="closePriority"
            >
                <!-- PRIORITY NUMBER BUTTONS -->
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

                <!-- CLEAR BUTTON -->
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
