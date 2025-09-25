<script setup lang="ts">
import { ref, computed, watchEffect } from 'vue';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { UserLite } from '@/types/shifts';
import { Plus } from 'lucide-vue-next';

type UserRole = 'admin' | 'employee' | 'customer' | string;

type Filters = {
    date: string;
    service: string;
    customer_id: number | '' | null;
    employee_id: number | '' | null;
    status: string;
};

const props = defineProps<{
    role: UserRole;
    customers: UserLite[];
    employees: UserLite[];
    initial?: Partial<Filters>;
}>();

const emit = defineEmits<{
    (e: 'apply', payload: Partial<Filters>): void;
    (e: 'clear'): void;
}>();

const show = ref(false);

const filters = ref<Filters>({
    date: '',
    service: '',
    customer_id: '',
    employee_id: '',
    status: '',
});

// Prefill from initial
watchEffect(() => {
    if (!props.initial) return;
    filters.value.date = props.initial.date ?? '';
    filters.value.service = props.initial.service ?? '';
    filters.value.customer_id = (props.initial.customer_id ?? '') as any;
    filters.value.employee_id = (props.initial.employee_id ?? '') as any;
    filters.value.status = props.initial.status ?? '';
});

// 1) Define a safe key union
type RoleKey = 'admin' | 'customer' | 'employee' | 'default';

// 2) Make ROLE_FILTERS strongly typed
const ROLE_FILTERS = {
    admin:    ['date','service','customer_id','employee_id','status'],
    customer: ['date','service','employee_id','status'],
    employee: ['date','service','customer_id','status'],
    default:  ['date','service','status'],
} as const satisfies Record<RoleKey, readonly (keyof Filters)[]>;

// 3) Type guard / normalizer for props.role
function toRoleKey(r: unknown): RoleKey {
    return r === 'admin' || r === 'customer' || r === 'employee' ? r : 'default';
}

// 4) Compute a safe key, then index
const roleKey = computed<RoleKey>(() => toRoleKey(props.role));
const visible = computed(() => ROLE_FILTERS[roleKey.value]);
const showField = (k:any) => visible.value.includes(k);
// Static service options
const serviceOptions = [
    { label: 'All', value: '' },
    { label: 'Massor', value: 'Massor' },
    { label: 'Hudterapeut', value: 'Hudterapeut' },
];

// Status options
const statusOptions = [
    { label: 'All', value: '' },
    { label: 'Open', value: 'open' },
    { label: 'Booked', value: 'booked' },
    { label: 'Completed', value: 'completed' },
    { label: 'Cancel', value: 'cancel' },
];

// Actions
const apply = () => {
    const q: Partial<Filters> = {};
    if (filters.value.date) q.date = filters.value.date;
    if (filters.value.service) q.service = filters.value.service;
    if (showField('customer_id') && filters.value.customer_id) q.customer_id = filters.value.customer_id;
    if (showField('employee_id') && filters.value.employee_id) q.employee_id = filters.value.employee_id;
    if (filters.value.status) q.status = filters.value.status;

    emit('apply', q);
    show.value = false;
};

const clear = () => {
    filters.value = { date: '', service: '', customer_id: '', employee_id: '', status: '' };
    emit('clear');
    show.value = false;
};
</script>

<template>
    <div>
        <Button  @click="show = true"><Plus class="mr-2 h-4 w-4" />Filters</Button>

        <Dialog :open="show" @update:open="val => (show = val)">
            <DialogContent class="max-w-xl">
                <DialogHeader>
                    <DialogTitle>Filter Shifts</DialogTitle>
                    <DialogDescription>Select criteria to refine the shift list.</DialogDescription>
                </DialogHeader>

                <div class="grid gap-3">
                    <div v-if="showField('date')">
                        <label class="block text-sm font-medium mb-1">Date</label>
                        <input type="date" v-model="filters.date" class="w-full rounded-md border px-3 py-2 bg-background" />
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
