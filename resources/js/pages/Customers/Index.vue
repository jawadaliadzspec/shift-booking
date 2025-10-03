<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Plus, Edit, Trash2 } from 'lucide-vue-next';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogContent,
    AlertDialogHeader,
    AlertDialogFooter,
    AlertDialogTitle,
    AlertDialogDescription,
    AlertDialogCancel,
    AlertDialogAction,
} from '@/components/ui/alert-dialog';
import CustomerForm from '@/pages/Customers/CustomerForm.vue';
import { ref } from 'vue';

type Customer = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    hourly_rate: number | null;
};

const props = defineProps<{
    customers: Customer[];
}>();

// Create/Edit modal state
const showCreate = ref(false);
const showEdit = ref(false);
const selected = ref<Customer | null>(null);

const openCreate = () => { showCreate.value = true; };
const openEdit = (c: Customer) => {
    selected.value = c;
    showEdit.value = true;
};

const onSaved = () => {
    showCreate.value = false;
    showEdit.value = false;
    selected.value = null;
    router.reload({ only: ['customers'] });

};

// Delete confirm state
const showDelete = ref(false);
const toDeleteId = ref<number | null>(null);

const askDelete = (id: number) => {
    toDeleteId.value = id;
    showDelete.value = true;
};

const deleteCustomer = () => {
    if (!toDeleteId.value) return;
    router.delete(`/customers/${toDeleteId.value}`, {
        preserveScroll: true,
        onFinish: () => {
            showDelete.value = false;
            toDeleteId.value = null;
        },
    });
};
</script>

<template>
    <AppLayout>
        <Head title="Customers" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Customers</h2>
                    <p class="text-muted-foreground">Manage your customers here.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <Button @click="openCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Customer
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div v-if="props.customers.length" class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Hourly Rate</TableHead>
                            <TableHead class="w-[140px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="customer in props.customers" :key="customer.id">
                            <TableCell>{{ customer.name }}</TableCell>
                            <TableCell>{{ customer.email }}</TableCell>
                            <TableCell>{{ customer.phone || 'N/A' }}</TableCell>
                            <TableCell>
                                {{ customer.hourly_rate !== null ? `${customer.hourly_rate}` : 'N/A' }}
                            </TableCell>
                            <TableCell>
                                <div class="flex space-x-2">
                                    <Button variant="outline" size="sm" @click="openEdit(customer)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="askDelete(customer.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div v-else class="text-center py-12">
                <p class="text-muted-foreground">No customers found.</p>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog :open="showCreate" @update:open="val => showCreate = val">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Create Customer</DialogTitle>
                    <DialogDescription>Fill in the details to add a new customer.</DialogDescription>
                </DialogHeader>
                <CustomerForm mode="create" @cancel="showCreate = false" @saved="onSaved" />
            </DialogContent>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog :open="showEdit" @update:open="val => showEdit = val">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Edit Customer</DialogTitle>
                    <DialogDescription>Update the customer details.</DialogDescription>
                </DialogHeader>
                <CustomerForm
                    v-if="selected"
                    mode="edit"
                    :customer="selected"
                    @cancel="showEdit = false"
                    @saved="onSaved"
                />
            </DialogContent>
        </Dialog>

        <!-- Delete Confirm -->
        <AlertDialog :open="showDelete" @update:open="val => showDelete = val">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Customer</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to delete this customer? This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDelete = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="deleteCustomer"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
