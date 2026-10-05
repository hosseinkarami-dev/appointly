@props(['wireModel', 'value', 'today'])

<div
    x-data="{
        open: false,
        panel: 'days',
        selected: @js($value ?: $today),
        month: @js(substr((string) ($value ?: $today), 0, 7)),
        today: @js($today),
        weekdays: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        months: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
        get monthLabel() {
            const [year, month] = this.month.split('-').map(Number);
            return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
        },
        get days() {
            const [year, month] = this.month.split('-').map(Number);
            const firstWeekday = (new Date(year, month - 1, 1).getDay() + 6) % 7;
            const count = new Date(year, month, 0).getDate();
            return [...Array(firstWeekday).fill(null), ...Array.from({ length: count }, (_, index) => `${year}-${String(month).padStart(2, '0')}-${String(index + 1).padStart(2, '0')}`)];
        },
        get selectedLabel() {
            if (!this.selected) return 'Choose a date';
            const [year, month, day] = this.selected.split('-').map(Number);
            return new Intl.DateTimeFormat(undefined, { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(year, month - 1, day));
        },
        shiftMonth(amount) {
            const [year, month] = this.month.split('-').map(Number);
            const date = new Date(year, month - 1 + amount, 1);
            this.month = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
        },
        shiftYear(amount) {
            const [year, month] = this.month.split('-').map(Number);
            this.month = `${year + amount}-${String(month).padStart(2, '0')}`;
        },
        chooseMonth(monthIndex) {
            const [year] = this.month.split('-').map(Number);
            this.month = `${year}-${String(monthIndex + 1).padStart(2, '0')}`;
            this.panel = 'days';
        },
        choose(date) {
            this.selected = date;
            this.month = date.slice(0, 7);
            this.open = false;
            $wire.$set(@js($wireModel), date);
        },
        init() {
            this.$wire.$watch(@js($wireModel), (value) => {
                if (!value) return;
                this.selected = value;
                this.month = value.slice(0, 7);
            });
        }
    }"
    @click.outside="open = false"
    @keydown.escape.stop.prevent="open = false"
    class="relative"
>
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="dialog" class="inline-flex min-h-10 min-w-0 cursor-pointer items-center gap-2 rounded-xl border border-[#171323]/10 bg-[#f6f5f2] px-3 py-2 text-sm font-semibold text-[#171323] transition hover:border-violet-300 hover:bg-white focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-violet-500/10 dark:border-white/10 dark:bg-[#211c31] dark:text-white dark:hover:border-violet-300/50 dark:hover:bg-[#2a2340]">
        <i data-feather="calendar" class="size-4 shrink-0 text-violet-600 dark:text-violet-300"></i>
        <span x-text="selectedLabel" class="truncate"></span>
        <i data-feather="chevron-down" class="size-4 shrink-0 text-[#171323]/45 transition dark:text-white/45" :class="open && 'rotate-180'"></i>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.left role="dialog" aria-label="Choose calendar date" class="absolute left-0 top-full z-40 mt-2 w-[min(20rem,calc(100vw-2.5rem))] rounded-2xl border border-[#171323]/10 bg-white p-4 shadow-2xl shadow-[#171323]/15 dark:border-white/10 dark:bg-[#211c31] dark:shadow-black/30">
        <div class="mb-4 flex items-center justify-between">
            <button type="button" @click="panel === 'days' ? shiftMonth(-1) : shiftYear(-1)" :aria-label="panel === 'days' ? 'Previous month' : 'Previous year'" class="grid size-9 cursor-pointer place-items-center rounded-xl text-[#171323]/55 transition hover:bg-violet-50 hover:text-violet-700 dark:text-white/55 dark:hover:bg-violet-400/10 dark:hover:text-violet-300"><i data-feather="chevron-left" class="size-4"></i></button>
            <button type="button" @click="panel = panel === 'days' ? 'months' : 'days'" class="cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-violet-50 dark:hover:bg-violet-400/10" x-text="panel === 'days' ? monthLabel : month.split('-')[0]" :aria-label="panel === 'days' ? 'Choose month and year' : 'Back to calendar'"></button>
            <button type="button" @click="panel === 'days' ? shiftMonth(1) : shiftYear(1)" :aria-label="panel === 'days' ? 'Next month' : 'Next year'" class="grid size-9 cursor-pointer place-items-center rounded-xl text-[#171323]/55 transition hover:bg-violet-50 hover:text-violet-700 dark:text-white/55 dark:hover:bg-violet-400/10 dark:hover:text-violet-300"><i data-feather="chevron-right" class="size-4"></i></button>
        </div>
        <div x-show="panel === 'days'" class="grid grid-cols-7 gap-1 text-center">
            <template x-for="weekday in weekdays" :key="weekday"><span class="py-1.5 text-[0.65rem] font-bold uppercase tracking-wide text-[#171323]/40 dark:text-white/40" x-text="weekday"></span></template>
            <template x-for="(day, index) in days" :key="day || `empty-${index}`">
                <span>
                    <button x-show="day" type="button" @click="choose(day)" :aria-label="day" :aria-pressed="selected === day" :class="selected === day ? 'bg-violet-600 font-semibold text-white shadow-md shadow-violet-600/20' : 'text-[#171323]/75 hover:bg-violet-50 hover:text-violet-700 dark:text-white/75 dark:hover:bg-violet-400/10 dark:hover:text-violet-200'" class="grid size-9 cursor-pointer place-items-center rounded-xl text-sm tabular-nums transition" x-text="day ? Number(day.slice(-2)) : ''"></button>
                </span>
            </template>
        </div>
        <div x-cloak x-show="panel === 'months'" x-transition class="grid grid-cols-3 gap-2">
            <template x-for="(monthName, index) in months" :key="monthName">
                <button type="button" @click="chooseMonth(index)" :class="Number(month.split('-')[1]) === index + 1 ? 'bg-violet-100 font-semibold text-violet-800 dark:bg-violet-400/15 dark:text-violet-200' : 'text-[#171323]/70 hover:bg-violet-50 hover:text-violet-700 dark:text-white/70 dark:hover:bg-violet-400/10 dark:hover:text-violet-200'" class="cursor-pointer rounded-xl px-2 py-3 text-xs transition" x-text="monthName.slice(0, 3)"></button>
            </template>
        </div>
        <div class="mt-3 border-t border-[#171323]/8 pt-3 text-right dark:border-white/10">
            <button type="button" @click="choose(today)" class="cursor-pointer rounded-lg px-3 py-2 text-xs font-semibold text-violet-700 transition hover:bg-violet-50 dark:text-violet-300 dark:hover:bg-violet-400/10">Today</button>
        </div>
    </div>
</div>
