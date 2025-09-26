<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { ChevronDown, X } from 'lucide-vue-next';
import { ref } from 'vue';

type Customer = { id: number; name: string; email: string };

type Employee = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    hourly_rate: number | null;
    // NEW: prefilled on edit
    customer_ids?: number[];
};

const props = withDefaults(defineProps<{
    mode: 'create' | 'edit';
    employee?: Employee | null;
    // NEW: options for the multi-select
    customers: Customer[];
}>(), {
    employee: null,
});

const emit = defineEmits<{ (e: 'cancel'): void; (e: 'saved'): void }>();

const form = useForm({
    name: props.employee?.name ?? '',
    email: props.employee?.email ?? '',
    phone: props.employee?.phone ?? '',
    hourly_rate: props.employee?.hourly_rate ?? '',
    password: props.mode === 'create' ? '' : '',
    password_confirmation: props.mode === 'create' ? '' : '',

    // NEW: multi-customer binding
    customer_ids: (props.employee?.customer_ids ?? []) as number[],
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

// Multi-select functionality
const isOpen = ref(false);
const searchTerm = ref('');
const filteredCustomers = ref(props.customers);

const getCustomerName = (customerId: number) => {
    const customer = props.customers.find(c => c.id === customerId);
    return customer ? customer.name : '';
};

const removeCustomer = (customerId: number) => {
    form.customer_ids = form.customer_ids.filter(id => id !== customerId);
};

const toggleCustomer = (customerId: number) => {
    const index = form.customer_ids.indexOf(customerId);
    if (index > -1) {
        form.customer_ids.splice(index, 1);
    } else {
        form.customer_ids.push(customerId);
    }
};

const filterCustomers = () => {
    if (!searchTerm.value) {
        filteredCustomers.value = props.customers;
        return;
    }

    const term = searchTerm.value.toLowerCase();
    filteredCustomers.value = props.customers.filter(customer =>
        customer.name.toLowerCase().includes(term) ||
        customer.email.toLowerCase().includes(term)
    );
};

// Initialize filtered customers
filteredCustomers.value = props.customers;
</script>

<template>
    <div class="space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex flex-col space-y-2">
                <Label for="full_name">Full Name</Label>
                <Input id="full_name" v-model="form.name" type="text" placeholder="Enter full name" />
                <p v-if="form.errors.name" class="text-red-500 text-sm">{{ form.errors.name }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="email">Email</Label>
                <Input id="email" v-model="form.email" type="email" placeholder="Enter email" />
                <p v-if="form.errors.email" class="text-red-500 text-sm">{{ form.errors.email }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="phone">Phone</Label>
                <Input id="phone" v-model="form.phone" type="text" placeholder="Enter phone number" />
                <p v-if="form.errors.phone" class="text-red-500 text-sm">{{ form.errors.phone }}</p>
            </div>

            <div class="flex flex-col space-y-2">
                <Label for="hourly_rate">Hourly Rate</Label>
                <Input id="hourly_rate" v-model="form.hourly_rate" type="number" placeholder="Enter hourly rate" />
                <p v-if="form.errors.hourly_rate" class="text-red-500 text-sm">{{ form.errors.hourly_rate }}</p>
            </div>

<!--            &lt;!&ndash; Passwords &ndash;&gt;-->
<!--            <div class="flex flex-col space-y-2">-->
<!--                <Label for="password">{{ props.mode === 'create' ? 'Password' : 'New Password (optional)' }}</Label>-->
<!--                <Input id="password" v-model="form.password" type="password" placeholder="Enter password" />-->
<!--                <p v-if="form.errors.password" class="text-red-500 text-sm">{{ form.errors.password }}</p>-->
<!--            </div>-->

<!--            <div class="flex flex-col space-y-2">-->
<!--                <Label for="password_confirmation">{{ props.mode === 'create' ? 'Confirm Password' : 'Confirm New Password' }}</Label>-->
<!--                <Input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="Confirm password" />-->
<!--                <p v-if="form.errors.password_confirmation" class="text-red-500 text-sm">{{ form.errors.password_confirmation }}</p>-->
<!--            </div>-->

            <!-- Multi-select Customers -->
            <div class="flex flex-col space-y-2 md:col-span-2">
                <Label for="customers">Customers</Label>
                <div class="relative">
                    <div
                        @click="isOpen = !isOpen"
                        class="border rounded-lg p-3 cursor-pointer min-h-[48px] flex items-center justify-between hover:border-gray-400 transition-colors"
                    >
                        <div class="flex flex-wrap gap-1 flex-1">
                            <span
                                v-if="form.customer_ids.length === 0"
                                class="text-gray-500"
                            >
                                Select customers...
                            </span>
                            <Badge
                                v-for="customerId in form.customer_ids"
                                :key="customerId"
                                variant="secondary"
                                class="mr-1 mb-1"
                            >
                                {{ getCustomerName(customerId) }}
                                <button
                                    type="button"
                                    @click.stop="removeCustomer(customerId)"
                                    class="ml-1 hover:text-destructive"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </Badge>
                        </div>
                        <ChevronDown
                            :class="['h-4 w-4 text-gray-500 transition-transform', { 'rotate-180': isOpen }]"
                        />
                    </div>

                    <div
                        v-if="isOpen"
                        @click.away="isOpen = false"
                        class="absolute top-full left-0 right-0 mt-1 border rounded-lg bg-white shadow-lg z-50 max-h-60 overflow-y-auto"
                    >
                        <div class="p-2">
<!--                            <Input-->
<!--                                v-model="searchTerm"-->
<!--                                placeholder="Search customers..."-->
<!--                                class="mb-2"-->
<!--                                @input="filterCustomers"-->
<!--                            />-->
                            <div
                                v-for="customer in filteredCustomers"
                                :key="customer.id"
                                @click="toggleCustomer(customer.id)"
                                class="p-2 hover:bg-gray-100 rounded cursor-pointer flex items-center"
                            >
                                <div
                                    :class="[
                                        'w-4 h-4 border rounded mr-3 flex items-center justify-center',
                                        form.customer_ids.includes(customer.id) ? 'bg-primary border-primary' : 'border-gray-300'
                                    ]"
                                >
                                    <div
                                        v-if="form.customer_ids.includes(customer.id)"
                                        class="w-2 h-2 bg-white rounded-sm"
                                    />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="font-medium text-sm">{{ customer.name }}</div>
                                    <div class="text-xs text-gray-500">{{ customer.email }}</div>
                                </div>
                            </div>
                            <p v-if="filteredCustomers.length === 0" class="text-sm text-gray-500 text-center py-2">
                                No customers found
                            </p>
                        </div>
                    </div>
                </div>
                <p v-if="form.errors.customer_ids" class="text-red-500 text-sm">{{ form.errors.customer_ids }}</p>
            </div>
        </div>

        <div class="flex justify-end space-x-3">
            <Button type="button" variant="outline" @click="emit('cancel')">Cancel</Button>
<!--            <Button v-if="props.mode === 'edit'" type="button" variant="secondary" @click="clearPassword">Clear Password</Button>-->
            <Button :disabled="form.processing" @click="submit">
                {{ form.processing ? (props.mode === 'create' ? 'Creating...' : 'Updating...') : (props.mode === 'create' ? 'Create Employee' : 'Update Employee') }}
            </Button>
        </div>
    </div>
</template>
