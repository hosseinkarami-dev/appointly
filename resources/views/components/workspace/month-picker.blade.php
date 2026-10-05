@props(['wireModel', 'value'])

<div
    x-data="{
        open: false,
        showingYears: false,
        month: @js($value),
        get year() {
            return Number(this.month.slice(0, 4));
        },
        get monthNumber() {
            return Number(this.month.slice(5, 7));
        },
        get label() {
            const [year, month] = this.month.split('-').map(Number);
            return new Intl.DateTimeFormat(undefined, { month: 'long', year: 'numeric' }).format(new Date(year, month - 1, 1));
        },
        get years() {
            const start = Math.floor(this.year / 12) * 12;
            return Array.from({ length: 12 }, (_, index) => start + index);
        },
        moveYear(amount) {
            this.month = `${this.year + amount}-${String(this.monthNumber).padStart(2, '0')}`;
        },
        moveDecade(amount) {
            const year = this.year + amount * 12;
            this.month = `${year}-${String(this.monthNumber).padStart(2, '0')}`;
        },
        chooseYear(year) {
            this.month = `${year}-${String(this.monthNumber).padStart(2, '0')}`;
            this.showingYears = false;
        },
        chooseMonth(month) {
            this.month = `${this.year}-${String(month).padStart(2, '0')}`;
            this.open = false;
            $wire.$set(@js($wireModel), this.month);
        },
        init() {
            this.$wire.$watch(@js($wireModel), value => {
                if (value) this.month = value;
            });
        }
    }"
    @click.outside="open = false; showingYears = false"
    @keydown.escape.stop.prevent="open = false; showingYears = false"
    class="relative"
>
    <button type="button" @click="open = !open" :aria-expanded="open.toString()" aria-haspopup="dialog" class="inline-flex min-h-11 min-w-52 cursor-pointer items-center justify-between gap-3 rounded-xl border border-[#171323]/10 bg-white px-4 py-2.5 text-sm font-semibold text-[#171323] shadow-sm transition hover:border-violet-300 hover:bg-violet-50 focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-violet-500/10 dark:border-white/15 dark:bg-[#211c31] dark:text-white dark:hover:border-violet-300/50 dark:hover:bg-[#2a2340]">
        <span class="flex items-center gap-2.5"><i data-feather="calendar" class="size-4 text-violet-600 dark:text-violet-300"></i><span x-text="label"></span></span>
        <i data-feather="chevron-down" class="size-4 text-[#171323]/45 transition dark:text-white/45" :class="open && 'rotate-180'"></i>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top.right role="dialog" aria-label="Choose report month" class="absolute right-0 z-40 mt-2 w-72 rounded-2xl border border-[#171323]/10 bg-white p-4 shadow-2xl shadow-[#171323]/15 dark:border-white/15 dark:bg-[#211c31] dark:shadow-black/30">
        <div class="mb-4 flex items-center justify-between gap-2">
            <button type="button" @click="showingYears ? moveDecade(-1) : moveYear(-1)" :aria-label="showingYears ? 'Previous group of years' : 'Previous year'" class="grid size-9 cursor-pointer place-items-center rounded-xl text-[#171323]/55 transition hover:bg-violet-50 hover:text-violet-700 dark:text-white/65 dark:hover:bg-violet-400/15 dark:hover:text-violet-200"><i data-feather="chevron-left" class="size-4"></i></button>
            <button type="button" @click="showingYears = !showingYears" class="cursor-pointer rounded-lg px-3 py-2 text-sm font-semibold transition hover:bg-violet-50 dark:hover:bg-violet-400/15" x-text="showingYears ? `${years[0]} – ${years[years.length - 1]}` : year" :aria-label="showingYears ? 'Choose a year' : 'Choose year range'"></button>
            <button type="button" @click="showingYears ? moveDecade(1) : moveYear(1)" :aria-label="showingYears ? 'Next group of years' : 'Next year'" class="grid size-9 cursor-pointer place-items-center rounded-xl text-[#171323]/55 transition hover:bg-violet-50 hover:text-violet-700 dark:text-white/65 dark:hover:bg-violet-400/15 dark:hover:text-violet-200"><i data-feather="chevron-right" class="size-4"></i></button>
        </div>

        <div x-cloak x-show="showingYears" class="grid grid-cols-3 gap-2">
            <template x-for="yearOption in years" :key="yearOption">
                <button type="button" @click="chooseYear(yearOption)" :class="yearOption === year ? 'bg-violet-100 font-semibold text-violet-800 dark:bg-violet-400/20 dark:text-violet-200' : 'text-[#171323]/70 hover:bg-violet-50 hover:text-violet-700 dark:text-white/75 dark:hover:bg-violet-400/15 dark:hover:text-violet-200'" class="cursor-pointer rounded-xl px-2 py-3 text-sm tabular-nums transition" x-text="yearOption"></button>
            </template>
        </div>
        <div x-cloak x-show="!showingYears" class="grid grid-cols-3 gap-2">
            <template x-for="(monthName, index) in ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December']" :key="monthName">
                <button type="button" @click="chooseMonth(index + 1)" :class="monthNumber === index + 1 ? 'bg-violet-100 font-semibold text-violet-800 dark:bg-violet-400/20 dark:text-violet-200' : 'text-[#171323]/70 hover:bg-violet-50 hover:text-violet-700 dark:text-white/75 dark:hover:bg-violet-400/15 dark:hover:text-violet-200'" class="cursor-pointer rounded-xl px-2 py-3 text-xs transition" x-text="monthName.slice(0, 3)"></button>
            </template>
        </div>
    </div>
</div>
