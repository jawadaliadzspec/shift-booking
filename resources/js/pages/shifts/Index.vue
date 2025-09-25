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
import ShiftForm from '@/pages/shifts/ShiftForm.vue';
import { ref } from 'vue';

type UserLite = { id: number; name: string; email: string };
type ShiftLite = {
    id: number;
    date: string;
    start_time: string;
    end_time: string;
    service: string;
    status: string;
    customer: { id: number; name: string; email: string };
    employee: { id: number; name: string; email: string };
};

const props = defineProps<{
    shifts: ShiftLite[];
    customers: UserLite[];
    employees: UserLite[];
}>();

// Create/Edit modal state
const showCreate = ref(false);
const showEdit = ref(false);
const selected = ref<ShiftLite | null>(null);

const openCreate = () => { showCreate.value = true; };
const openEdit = (s: ShiftLite) => {
    selected.value = s;
    showEdit.value = true;
};

const onSaved = () => {
    showCreate.value = false;
    showEdit.value = false;
    selected.value = null;
    router.reload({ only: ['shifts'] });
};

// Delete confirm state
const showDelete = ref(false);
const toDeleteId = ref<number | null>(null);

const askDelete = (id: number) => {
    toDeleteId.value = id;
    showDelete.value = true;
};

const deleteShift = () => {
    if (!toDeleteId.value) return;
    router.delete(`/shifts/${toDeleteId.value}`, {
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
        <Head title="Shifts" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Shifts</h2>
                    <p class="text-muted-foreground">Manage scheduled shifts here.</p>
                </div>
                <div class="flex items-center space-x-2">
                    <Button @click="openCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Shift
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div v-if="props.shifts.length" class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Time</TableHead>
                            <TableHead>Service</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Customer</TableHead>
                            <TableHead>Employee</TableHead>
                            <TableHead class="w-[140px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="shift in props.shifts" :key="shift.id">
                            <TableCell>{{ shift.date }}</TableCell>
                            <TableCell>{{ shift.start_time }}–{{ shift.end_time }}</TableCell>
                            <TableCell>{{ shift.service }}</TableCell>
                            <TableCell>{{ shift.status }}</TableCell>
                            <TableCell>{{ shift.customer?.name || '—' }}</TableCell>
                            <TableCell>{{ shift.employee?.name || '—' }}</TableCell>
                            <TableCell>
                                <div class="flex space-x-2">
                                    <Button variant="outline" size="sm" @click="openEdit(shift)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button variant="destructive" size="sm" @click="askDelete(shift.id)">
                                        <Trash2 class="h-4 w-4" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div v-else class="text-center py-12">
                <p class="text-muted-foreground">No shifts found.</p>
            </div>
        </div>

        <!-- Create Modal -->
        <Dialog :open="showCreate" @update:open="val => showCreate = val">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Create Shift</DialogTitle>
                    <DialogDescription>Fill in the details to add a new shift.</DialogDescription>
                </DialogHeader>
                <ShiftForm
                    mode="create"
                    :customers="props.customers"
                    :employees="props.employees"
                    @cancel="showCreate = false"
                    @saved="onSaved"
                />
            </DialogContent>
        </Dialog>

        <!-- Edit Modal -->
        <Dialog :open="showEdit" @update:open="val => showEdit = val">
            <DialogContent class="max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Edit Shift</DialogTitle>
                    <DialogDescription>Update the shift details.</DialogDescription>
                </DialogHeader>
                <ShiftForm
                    v-if="selected"
                    mode="edit"
                    :shift="selected"
                    :customers="props.customers"
                    :employees="props.employees"
                    @cancel="showEdit = false"
                    @saved="onSaved"
                />
            </DialogContent>
        </Dialog>

        <!-- Delete Confirm -->
        <AlertDialog :open="showDelete" @update:open="val => showDelete = val">
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>Delete Shift</AlertDialogTitle>
                    <AlertDialogDescription>
                        Are you sure you want to delete this shift? This action cannot be undone.
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel @click="showDelete = false">Cancel</AlertDialogCancel>
                    <AlertDialogAction
                        class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                        @click="deleteShift"
                    >
                        Delete
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    </AppLayout>
</template>
