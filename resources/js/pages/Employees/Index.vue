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
import EmployeeForm from '@/pages/Employees/EmployeeForm.vue'; // you'll create this, same style as CustomerForm
import { ref } from 'vue';

type Employee = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    hourly_rate: number | null;
    customers?: Array<{ id: number; name: string; email: string }>;
};

const props = defineProps<{
    employees: Employee[];
    customers: Array<{ id:number; name:string; email:string }>
}>();

// Create/Edit modal state
const showCreate = ref(false);
const showEdit = ref(false);
const selected = ref<Employee | null>(null);

const openCreate = () => { showCreate.value = true; };
const openEdit = (e: any) => {
    selected.value = {
        ...e,
        customer_ids: (e.customer_ids ?? e.customers?.map((c: any) => c.id) ?? []).map(Number),
    };
    showEdit.value = true;
};

const onSaved = () => {
    showCreate.value = false;
    showEdit.value = false;
    selected.value = null;
    router.reload({ only: ['employees'] });
};

// Delete confirm state
const showDelete = ref(false);
const toDeleteId = ref<number | null>(null);

const askDelete = (id: number) => {
    toDeleteId.value = id;
    showDelete.value = true;
};

const deleteEmployee = () => {
    if (!toDeleteId.value) return;
    router.delete(`/employees/${toDeleteId.value}`, {
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
        <Head title="Employees" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Employees</h2>
                    <p class="text-muted-foreground">Manage your employees here.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <Button @click="openCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Employee
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div v-if="props.employees.length" class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Full Name</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead>Phone</TableHead>
                            <TableHead>Hourly Rate</TableHead>
                            <TableHead class="w-[140px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="employee in props.employees" :key="employee.id">
                            <TableCell>{{ employee.name }}</TableCell>
                            <TableCell>{{ employee.email }}</TableCell>
                            <TableCell>{{ employee.phone || 'N/A' }}</TableCell>
                            <TableCell>
                                {{ employee.hourly_rate !== null ? `${employee.hourly_rate}` : 'N/A' }}
                            </TableCell>
                            <TableCell>
                                <div class="flex space-x-2">
                                    <Button variant="outline" size="sm" @click="openEdit(employee)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="askDelete(employee.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div v-else class="text-center py-12">
                <p class="text-muted-foreground">No employees found.</p>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog :open="showCreate" @update:open="val => showCreate = val">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Create Employee</DialogTitle>
                    <DialogDescription>Fill in the details to add a new employee.</DialogDescription>
                </DialogHeader>
                <EmployeeForm mode="create" :customers="props.customers" @cancel="showCreate = false" @saved="onSaved" />
            </DialogContent>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog :open="showEdit" @update:open="val => showEdit = val">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Edit Employee</DialogTitle>
                    <DialogDescription>Update the employee details.</DialogDescription>
                </DialogHeader>
<!--                <EmployeeForm-->
<!--                    v-if="selected"-->
<!--                    mode="edit"-->
<!--                    :employee="selected"-->
<!--                    :customers="props.customers"-->
<!--                    @cancel="showEdit = false"-->
<!--                    @saved="onSaved"-->
<!--                />-->
                <EmployeeForm
                    v-if="selected"
                    :key="selected.id"
                mode="edit"
                :employee="selected"
                :customers="props.customers"
                @cancel="showEdit = false"
                @saved="onSaved"
                />
            </DialogContent>
        </Dialog>

        <!-- Delete Confirm -->
        <AlertDialog :open="showDelete" @update:open="val => showDelete = val">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Employee</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to delete this employee? This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDelete = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="deleteEmployee"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
