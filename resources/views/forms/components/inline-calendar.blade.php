<div
    x-data="{
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
        startDate: $wire.entangle('data.start_date'),
        endDate: $wire.entangle('data.end_date'),
        occupiedDates: @js($occupiedDates ?? []),

        get monthName() {
            const months = ['January', 'February', 'March', 'April', 'May', 'June',
                          'July', 'August', 'September', 'October', 'November', 'December'];
            return months[this.currentMonth];
        },

        get daysInMonth() {
            return new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
        },

        get firstDayOfMonth() {
            return new Date(this.currentYear, this.currentMonth, 1).getDay();
        },

        isStartDate(day) {
            if (!this.startDate) return false;
            const date = new Date(this.currentYear, this.currentMonth, day);
            const start = new Date(this.startDate);
            return date.toDateString() === start.toDateString();
        },

        isEndDate(day) {
            if (!this.endDate) return false;
            const date = new Date(this.currentYear, this.currentMonth, day);
            const end = new Date(this.endDate);
            return date.toDateString() === end.toDateString();
        },

        isOccupiedDate(day) {
            const date = new Date(this.currentYear, this.currentMonth, day);
            const dateStr = date.toISOString().split('T')[0];
            return this.occupiedDates.includes(dateStr);
        },

        selectDate(day) {
            const date = new Date(this.currentYear, this.currentMonth, day);
            const formattedDate = date.toISOString().split('T')[0];

            // If no start date, set start date
            if (!this.startDate) {
                this.startDate = formattedDate;
            }
            // If start date exists but no end date, set end date
            else if (!this.endDate) {
                this.endDate = formattedDate;
            }
            // If both exist, reset and start over
            else {
                this.startDate = formattedDate;
                this.endDate = null;
            }
        },

        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },

        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        }
    }"
    class="inline-calendar w-full"
>
    <!-- Calendar Header -->
    <div class="flex items-center justify-between mb-4">
        <button
            type="button"
            @click="prevMonth()"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </button>

        <div class="text-lg font-semibold">
            <span x-text="monthName"></span>
            <span x-text="currentYear"></span>
        </div>

        <button
            type="button"
            @click="nextMonth()"
            class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </button>
    </div>

    <!-- Day Headers -->
    <div class="grid grid-cols-7 gap-1 mb-2">
        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Sun</div>
        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Mon</div>
        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Tue</div>
        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Wed</div>
        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Thu</div>
        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Fri</div>
        <div class="text-center text-xs font-medium text-gray-500 dark:text-gray-400 py-2">Sat</div>
    </div>

    <!-- Calendar Days -->
    <div class="grid grid-cols-7 gap-1">
        <!-- Empty cells for days before month starts -->
        <template x-for="i in firstDayOfMonth" :key="'empty-' + i">
            <div class="aspect-square"></div>
        </template>

        <!-- Days of the month -->
        <template x-for="day in daysInMonth" :key="day">
            <button
                type="button"
                @click="selectDate(day)"
                class="aspect-square flex items-center justify-center text-sm rounded-lg transition-colors relative"
                :class="{
                    'bg-success-100 dark:bg-success-900/20 text-success-700 dark:text-success-400 font-semibold': isStartDate(day),
                    'bg-warning-100 dark:bg-warning-900/20 text-warning-700 dark:text-warning-400 font-semibold': isEndDate(day) && !isStartDate(day),
                    'bg-gray-200 dark:bg-gray-600 text-gray-500 dark:text-gray-400': isOccupiedDate(day) && !isStartDate(day) && !isEndDate(day),
                    'hover:bg-gray-100 dark:hover:bg-gray-700': !isStartDate(day) && !isEndDate(day) && !isOccupiedDate(day)
                }"
            >
                <span x-text="day"></span>
                <!-- Dot indicator for occupied dates -->
                <span
                    x-show="isOccupiedDate(day) && !isStartDate(day) && !isEndDate(day)"
                    class="absolute bottom-1 w-1 h-1 rounded-full bg-gray-400 dark:bg-gray-500"
                ></span>
            </button>
        </template>
    </div>

    <!-- Legend -->
    <div class="mt-4 flex items-center gap-4 text-xs">
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-success-100 dark:bg-success-900/20"></div>
            <span class="text-gray-600 dark:text-gray-400">Start dates</span>
        </div>
        <div class="flex items-center gap-2">
            <div class="w-3 h-3 rounded bg-warning-100 dark:bg-warning-900/20"></div>
            <span class="text-gray-600 dark:text-gray-400">Delivery dates</span>
        </div>
    </div>
</div>
