<script setup lang="ts">
import { ref, computed, watchEffect } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { UserLite } from '@/types/shifts';
import { Plus } from 'lucide-vue-next';

type UserRole = 'admin' | 'employee' | 'customer' | string;

type Filters = {
    date_from: string;
    date_to: string;
    service: string;
    customer_id: number | '' | null;
    employee_id: number | '' | null;
    status: string;
};

const props = defineProps<{
    role: UserRole;
    customers: UserLite[];
    employees: UserLite[];
    // keep legacy single-date for prefill only
    initial?: Partial<Filters> & { date?: string };
}>();

const emit = defineEmits<{
    (e: 'apply', payload: Partial<Filters>): void;
    (e: 'clear'): void;
}>();

const show = ref(false);

const filters = ref<Filters>({
    date_from: '',
    date_to: '',
    service: '',
    customer_id: '',
    employee_id: '',
    status: '',
});

// Prefill from initial (handles legacy `date`)
watchEffect(() => {
    if (!props.initial) return;
console.log( props.initial)
    filters.value.date_from = props.initial.date_from ?? '';
    filters.value.date_to   = props.initial.date_to   ?? '';

    // Legacy: if `initial.date` exists and range not set, use it for both ends
    if (props.initial.date && !filters.value.date_from && !filters.value.date_to) {
        filters.value.date_from = props.initial.date;
        filters.value.date_to   = props.initial.date;
    }

    filters.value.service      = props.initial.service ?? '';
    filters.value.customer_id  = (props.initial.customer_id ?? '') as any;
    filters.value.employee_id  = (props.initial.employee_id ?? '') as any;
    filters.value.status       = props.initial.status ?? '';
});

// Role-based visibility
type RoleKey = 'admin' | 'customer' | 'employee' | 'default';

const ROLE_FILTERS = {
    admin:    ['date_from','date_to','service','customer_id','employee_id','status'],
    customer: ['date_from','date_to','service','employee_id','status'],
    employee: ['date_from','date_to','service','customer_id','status'],
    default:  ['date_from','date_to','service','status'],
} as const satisfies Record<RoleKey, readonly (keyof Filters)[]>;

function toRoleKey(r: unknown): RoleKey {
    return r === 'admin' || r === 'customer' || r === 'employee' ? r : 'default';
}

const roleKey = computed<RoleKey>(() => toRoleKey(props.role));
const visible = computed(() => ROLE_FILTERS[roleKey.value]);
const showField = (k: keyof Filters) => visible.value.includes(k);

// Options
const serviceOptions = [
    { label: 'All', value: '' },
    { label: 'Massor', value: 'Massor' },
    { label: 'Hudterapeut', value: 'Hudterapeut' },
];

const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Open', value: 'open' },
    { label: 'Booked', value: 'booked' },
    { label: 'Completed', value: 'completed' },
    { label: 'Cancel', value: 'cancel' },
];

// Helpers
function normalizeRange() {
    const { date_from, date_to } = filters.value;
    if (date_from && date_to && date_from > date_to) {
        [filters.value.date_from, filters.value.date_to] = [date_to, date_from];
    }
}

// Actions
const apply = () => {
    normalizeRange();

    const q: Partial<Filters> = {};
    if (showField('date_from') && filters.value.date_from) q.date_from = filters.value.date_from;
    if (showField('date_to')   && filters.value.date_to)   q.date_to   = filters.value.date_to;
    if (filters.value.service) q.service = filters.value.service;
    if (showField('customer_id') && filters.value.customer_id) q.customer_id = filters.value.customer_id;
    if (showField('employee_id') && filters.value.employee_id) q.employee_id = filters.value.employee_id;
    if (filters.value.status) q.status = filters.value.status;

    emit('apply', q);
    show.value = false;
};

const clear = () => {
    filters.value = { date_from: '', date_to: '', service: '', customer_id: '', employee_id: '', status: '' };
    emit('clear');
    show.value = false;
};
</script>

<template>
    <div>
        <Button @click="show = true"><Plus class="mr-2 h-4 w-4" />Filters</Button>

        <Dialog :open="show" @update:open="val => (show = val)">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Filter Shifts</DialogTitle>
                    <DialogDescription>Select criteria to refine the shift list.</DialogDescription>
                </DialogHeader>

                <div class="grid gap-3">
                    <!-- Start / End Date (two inputs) -->
                    <div v-if="showField('date_from') || showField('date_to')">
                        <label class="block text-sm font-medium mb-1">Date range</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            <input
                                v-if="showField('date_from')"
                                type="date"
                                v-model="filters.date_from"
                                class="w-full rounded-md border px-3 py-2 bg-background"
                                placeholder="Start date"
                            />
                            <input
                                v-if="showField('date_to')"
                                type="date"
                                v-model="filters.date_to"
                                class="w-full rounded-md border px-3 py-2 bg-background"
                                placeholder="End date"
                            />
                        </div>
                    </div>

                    <div v-if="showField('service')">
                        <label class="block text-sm font-medium mb-1">Service</label>
                        <select v-model="filters.service" class="w-full rounded-md border px-3 py-2 bg-background">
                            <option v-for="opt in serviceOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>

                    <div v-if="showField('customer_id')">
                        <label class="block text-sm font-medium mb-1">Customer</label>
                        <select v-model="filters.customer_id" class="w-full rounded-md border px-3 py-2 bg-background">
                            <option :value="''">All</option>
                            <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                        </select>
                    </div>

                    <div v-if="showField('employee_id')">
                        <label class="block text-sm font-medium mb-1">Employee</label>
                        <select v-model="filters.employee_id" class="w-full rounded-md border px-3 py-2 bg-background">
                            <option :value="''">All</option>
                            <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }}</option>
                        </select>
                    </div>

                    <div v-if="showField('status')">
                        <label class="block text-sm font-medium mb-1">Status</label>
                        <select v-model="filters.status" class="w-full rounded-md border px-3 py-2 bg-background">
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 mt-6">
                    <Button variant="outline" @click="clear">Clear</Button>
                    <Button @click="apply">Apply</Button>
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
