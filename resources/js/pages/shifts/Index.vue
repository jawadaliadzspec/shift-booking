<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
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
import { Badge } from '@/components/ui/badge';

const statusColor: Record<string, string> = {
    open: 'bg-yellow-400 text-black',
    booked: 'bg-green-500 text-white',
    completed: 'bg-blue-500 text-white',
    cancel: 'bg-gray-400 text-white',
};
const getStatusClass = (status: string) => statusColor[status] ?? 'bg-neutral-400 text-white';
// ✅ shared types
import type { ShiftLite, UserLite } from '@/types/shifts';
import ShiftFilters from '@/pages/shifts/ShiftFilters.vue';

// ===== page props (these are from Inertia server response) =====
// Note: backend may omit customer/employee keys when null; so the raw type marks them optional.
type RawShift = Omit<ShiftLite, 'customer' | 'employee'> & {
    customer?: UserLite | null;
    employee?: UserLite | null;
};

const props = defineProps<{
    shifts: RawShift[];
    customers: UserLite[];
    employees: UserLite[];
}>();

// ===== auth + role-based permission (admin + customer can create) =====
type UserRole = 'admin' | 'employee' | 'customer' | string;
const page = usePage();
const userRole = computed<UserRole>(() => page.props?.auth?.user?.user_type ?? 'employee');

const CAN_CREATE_SHIFT: Record<UserRole, boolean> = {
    admin: true,
    customer: true,
    employee: false,
    default: false,
};
const canCreateShift = computed(() => CAN_CREATE_SHIFT[userRole.value] ?? false);

// ===== normalize shifts so customer/employee are NEVER undefined (only UserLite or null) =====
const shifts = computed<ShiftLite[]>(() =>
    props.shifts.map(s => ({
        ...s,
        customer: s.customer ?? null,
        employee: s.employee ?? null,
    })),
);

// ===== modal state =====
const showCreate = ref(false);
const showEdit = ref(false);
const selected = ref<ShiftLite | null>(null);

const openCreate = () => {
    if (!canCreateShift.value) return;
    showCreate.value = true;
};

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

// ===== delete confirm state =====
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

const initialFilters = computed(() => {
    const out: Record<string, any> = {};
    try {
        const u = new URL(window.location.href);
        out.date_from = u.searchParams.get('date_from') ?? '';
        out.date_to = u.searchParams.get('date_to') ?? '';
        out.service = u.searchParams.get('service') ?? '';
        out.customer_id = u.searchParams.get('customer_id') ?? '';
        out.employee_id = u.searchParams.get('employee_id') ?? '';
        out.status = u.searchParams.get('status') ?? '';
    } catch {}
    return out;
});

// handlers
const onApplyFilters = (q: Record<string, any>) => {
    router.get('/shifts', q, { preserveState: true, preserveScroll: true, replace: true });
};
const onClearFilters = () => {
    router.get('/shifts', {}, { preserveState: true, preserveScroll: true, replace: true });
};
</script>

<template>
    <AppLayout>
        <Head class="mt-5" title="Shifts" />

        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">Shifts</h2>
                    <p class="text-muted-foreground">Manage scheduled shifts here.</p>
                </div>
                <div class="flex items-center space-x-2" v-if="canCreateShift">
                    <Button @click="openCreate">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Shift
                    </Button>
                    <ShiftFilters
                        :role="userRole"
                        :customers="props.customers"
                        :employees="props.employees"
                        :initial="initialFilters"
                        @apply="onApplyFilters"
                        @clear="onClearFilters"
                    />
                </div>
            </div>
            <!-- Table -->
            <div v-if="shifts.length" class="rounded-md border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Date</TableHead>
                            <TableHead>Time</TableHead>
                            <TableHead>Service</TableHead>
                            <TableHead>Customer</TableHead>
                            <TableHead>Employee</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="w-[140px]">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="shift in shifts" :key="shift.id">
                            <TableCell>{{ shift.date }}</TableCell>
                            <TableCell>{{ shift.start_time }}–{{ shift.end_time }}</TableCell>
                            <TableCell>{{ shift.service }}</TableCell>
                            <TableCell>{{ shift.customer ? shift.customer.name : '—' }}</TableCell>
                            <TableCell>{{ shift.employee ? shift.employee.name : '—' }}</TableCell>
                            <TableCell>
                                <Badge :class="getStatusClass(shift.status)">
                                    {{ shift.status }}
                                </Badge>
                            </TableCell>
                            <TableCell>
                                <div class="flex space-x-2">
                                    <Button v-if="shift.status === 'open' || userRole === 'admin'" variant="outline" size="sm" @click="openEdit(shift)">
                                        <Edit class="h-4 w-4" />
                                    </Button>
                                    <Button v-if="shift.status === 'open' || userRole === 'admin'" variant="destructive" size="sm" @click="askDelete(shift.id)">
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

        <!-- Create Modal (gated) -->
        <Dialog v-if="canCreateShift" :open="showCreate" @update:open="val => (showCreate = val)">
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
        <Dialog :open="showEdit" @update:open="val => (showEdit = val)">
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
        <AlertDialog :open="showDelete" @update:open="val => (showDelete = val)">
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
