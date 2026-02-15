export interface AcademicYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    status: 'upcoming' | 'ongoing' | 'completed';
    current_quarter: string;
    created_at: string;
    updated_at: string;
}
