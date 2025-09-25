// src/types/shifts.ts
export type UserLite = {
    id: number;
    name: string;
    email: string;
};

export type ShiftLite = {
    id: number;
    date: string;       // ISO date "YYYY-MM-DD"
    start_time: string; // "HH:mm"
    end_time: string;   // "HH:mm"
    service: string;
    status: string;
    // Important: allow null (not undefined). We'll normalize undefined -> null in the page.
    customer: UserLite | null;
    employee: UserLite | null;
};
