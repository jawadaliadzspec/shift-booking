<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Employee = {
    id: number;
    full_name: string;
    email: string;
    phone: string | null;
    hourly_rate: number | null;
};

const props = withDefaults(defineProps<{
    mode: 'create' | 'edit';
    employee?: Employee | null;
}>(), {
    employee: null,
});

const emit = defineEmits<{
    (e: 'cancel'): void;
    (e: 'saved'): void;
}>();

const form = useForm({
    full_name: props.employee?.full_name ?? '',
    email: props.employee?.email ?? '',
    phone: props.employee?.phone ?? '',
    hourly_rate: props.employee?.hourly_rate ?? '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (props.mode === 'create') {
        form.post('/employees', {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    } else {
        form.put(`/employees/${props.employee!.id}`, {
            preserveScroll: true,
            onSuccess: () => emit('saved'),
        });
    }
};

const clearPassword = () => {
    form.password = '';
    form.password_confirmation = '';
};
</script>

<template>
    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col space-y-2">
                <Label for="full_name">Full Name</Label>
                <Input id="full_name" v-model="form.full_name" type="text" placeholder="Enter full name" />
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" placeholder="Enter email" />
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" v-model="form.phone" type="text" placeholder="Enter phone number" />
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="hourly_rate">Hourly Rate</Label>
                <Input id="hourly_rate" v-model="form.hourly_rate" type="number" placeholder="Enter hourly rate" />
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="password">{{ props.mode === 'create' ? 'Password' : 'New Password (optional)' }}</Label>
                <Input id="password" v-model="form.password" type="password" />
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="password_confirmation">Confirm Password</Label>
                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" />
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <Button type="button" variant="outline" @click="emit('cancel')">Cancel</Button>
            <Button v-if="props.mode === 'edit'" type="button" variant="secondary" @click="clearPassword">
                Clear Password
            </Button>
            <Button :disabled="form.processing" @click="submit">
                {{ form.processing ? (props.mode === 'create' ? 'Creating...' : 'Updating...') : (props.mode === 'create' ? 'Create Employee' : 'Update Employee') }}
            </Button>
        </div>
    </div>
</template>
