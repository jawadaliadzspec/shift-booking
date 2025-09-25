<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { ShiftLite, UserLite } from '@/types/shifts';

// type UserLite = { id: number; name: string; email: string };
// type ShiftLite = {
//     id: number;
//     date: string;
//     start_time: string;
//     end_time: string;
//     service: string;
//     customer: { id: number; name: string; email: string };
//     employee: { id: number; name: string; email: string };
//     status: string;
// };

const props = withDefaults(defineProps<{
    mode: 'create' | 'edit';
    shift?: ShiftLite | null;
    customers: UserLite[];
    employees: UserLite[];
}>(), {
    shift: null,
});

const emit = defineEmits<{
    (e: 'cancel'): void;
    (e: 'saved'): void;
}>();

const form = useForm({
    date:        props.shift?.date ?? '',
    start_time:  props.shift?.start_time ?? '',
    end_time:    props.shift?.end_time ?? '',
    service:     props.shift?.service ?? '',
    status:      props.shift?.status ?? 'open',
    customer_id: props.shift?.customer?.id ?? '',
    employee_id: props.shift?.employee?.id ?? '',
});

const submit = () => {
    if (props.mode === 'create') {
        form.post('/shifts', {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    } else {
        form.put(`/shifts/${props.shift!.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    }
};
</script>

<template>
    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col space-y-2">
                <Label for="date">Date</Label>
                <Input id="date" v-model="form.date" type="date" :class="{ 'border-destructive': !!form.errors.date }" />
                <p v-if="form.errors.date" class="text-destructive text-sm">{{ form.errors.date }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="service">Service</Label>
                <Input id="service" v-model="form.service" type="text" placeholder="e.g. Night shift"
                       :class="{ 'border-destructive': !!form.errors.service }" />
                <p v-if="form.errors.service" class="text-destructive text-sm">{{ form.errors.service }}</p>
            </div>

            <div v-if="props.mode === 'edit'" class="flex flex-col space-y-2">
                <Label for="status">Status</Label>
                <select id="status" v-model="form.status" class="border rounded-md h-10 px-3"
                        :class="{ 'border-destructive': !!form.errors.status }">
                    <option value="open">Open</option>
                    <option value="booked">Booked</option>
                    <option value="completed">Completed</option>
                    <option value="canceled">Canceled</option>
                </select>
                <p v-if="form.errors.status" class="text-destructive text-sm">{{ form.errors.status }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="start_time">Start Time</Label>
                <Input id="start_time" v-model="form.start_time" type="time" :class="{ 'border-destructive': !!form.errors.start_time }" />
                <p v-if="form.errors.start_time" class="text-destructive text-sm">{{ form.errors.start_time }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="end_time">End Time</Label>
                <Input id="end_time" v-model="form.end_time" type="time" :class="{ 'border-destructive': !!form.errors.end_time }" />
                <p v-if="form.errors.end_time" class="text-destructive text-sm">{{ form.errors.end_time }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="customer_id">Customer</Label>
                <select id="customer_id" v-model="form.customer_id" class="border rounded-md h-10 px-3"
                        :class="{ 'border-destructive': !!form.errors.customer_id }">
                    <option value="" disabled>Select customer</option>
                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }} — {{ c.email }}</option>
                </select>
                <p v-if="form.errors.customer_id" class="text-destructive text-sm">{{ form.errors.customer_id }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="employee_id">Employee</Label>
                <select id="employee_id" v-model="form.employee_id" class="border rounded-md h-10 px-3"
                        :class="{ 'border-destructive': !!form.errors.employee_id }">
                    <option value="" disabled>Select employee</option>
                    <option v-for="e in employees" :key="e.id" :value="e.id">{{ e.name }} — {{ e.email }}</option>
                </select>
                <p v-if="form.errors.employee_id" class="text-destructive text-sm">{{ form.errors.employee_id }}</p>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <Button type="button" variant="outline" @click="emit('cancel')">Cancel</Button>
            <Button :disabled="form.processing" @click="submit">
                {{ form.processing ? (props.mode === 'create' ? 'Creating...' : 'Updating...') : (props.mode === 'create' ? 'Create Shift' : 'Update Shift') }}
            </Button>
        </div>
    </div>
</template>
