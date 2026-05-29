@props([
    'fieldId' => 'student_id',
    'name' => 'student_id',
    'label' => 'Student',
    'selectedId' => '',
    'initialLabel' => '',
    'options' => [],
    'placeholder' => 'Type student name or admission no',
    'helper' => 'Type a student name or admission number, then choose from the list.',
    'required' => true,
])

<div
    x-data="{
        studentOptions: @js($options),
        studentId: @js((string) $selectedId),
        studentQuery: @js($initialLabel),
        studentOpen: false,
        get filteredStudents() {
            const query = this.studentQuery.toLowerCase().trim();

            if (! query) {
                return this.studentOptions.slice(0, 12);
            }

            return this.studentOptions
                .filter((student) => student.label.toLowerCase().includes(query))
                .slice(0, 12);
        },
        chooseStudent(student) {
            this.studentId = student.id;
            this.studentQuery = student.label;
            this.studentOpen = false;
        },
        syncStudentFromQuery() {
            const exactMatch = this.studentOptions.find((student) => student.label === this.studentQuery);

            if (exactMatch) {
                this.studentId = exactMatch.id;
                return;
            }

            this.studentId = '';
        }
    }"
    x-modelable="studentId"
>
    <x-input-label :for="$fieldId" :value="$label" />
    <input type="hidden" id="{{ $fieldId }}" name="{{ $name }}" x-model="studentId" @required($required)>
    <div class="relative mt-1">
        <input
            type="text"
            x-model="studentQuery"
            @focus="studentOpen = true"
            @input="studentOpen = true; syncStudentFromQuery()"
            @click.outside="studentOpen = false"
            placeholder="{{ $placeholder }}"
            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            autocomplete="off"
            @required($required)
        >
        <div
            x-show="studentOpen"
            x-cloak
            class="absolute z-20 mt-2 max-h-72 w-full overflow-y-auto rounded-md border border-slate-200 bg-white shadow-lg"
        >
            <template x-if="filteredStudents.length > 0">
                <div class="py-1">
                    <template x-for="student in filteredStudents" :key="student.id">
                        <button
                            type="button"
                            @click="chooseStudent(student)"
                            class="flex w-full items-center px-4 py-2 text-left text-sm text-slate-700 hover:bg-sky-50 hover:text-slate-900"
                            x-text="student.label"
                        ></button>
                    </template>
                </div>
            </template>
            <div x-show="filteredStudents.length === 0" class="px-4 py-3 text-sm text-slate-500">
                No students found.
            </div>
        </div>
    </div>
    <p class="mt-1 text-xs text-slate-500">{{ $helper }}</p>
    <x-input-error class="mt-2" :messages="$errors->get($name)" />
</div>
