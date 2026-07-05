import { Head, router, useForm } from '@inertiajs/react';
import { Download, Edit2, ListFilter, ShieldCheck, UserCheck, X } from 'lucide-react';
import { useMemo, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { upsert } from '@/routes/admin/teacher_profiles';
import type { BreadcrumbItem } from '@/types';
import InputError from '@/components/input-error';
import { SearchAutocompleteInput } from '@/components/ui/search-autocomplete-input';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Academic Controls', href: '/admin/academic-controls' },
    { title: 'Teacher Profiles', href: '/admin/teacher-profiles' },
];

interface TeacherProfilePayload {
    qualification_status:
        | 'fully_qualified'
        | 'provisionally_qualified'
        | 'not_qualified';
    is_let_passer: boolean;
    prc_license_no: string | null;
    license_valid_until: string | null;
    degree: string | null;
    major: string | null;
    professional_education_units: number | null;
    exception_basis: string | null;
    provisional_until: string | null;
    notes: string | null;
    eligibility_documents: string[];
}

interface Teacher {
    id: number;
    name: string;
    email: string;
    profile: TeacherProfilePayload;
}

interface Props {
    teachers: Teacher[];
}

export default function TeacherProfiles({ teachers }: Props) {
    const [selectedTeacher, setSelectedTeacher] = useState<Teacher | null>(null);
    const [isEditOpen, setIsEditOpen] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [statusFilter, setStatusFilter] = useState('all');

    const form = useForm({
        _method: 'patch',
        qualification_status: 'not_qualified',
        is_let_passer: false,
        prc_license_no: '',
        license_valid_until: '',
        degree: '',
        major: '',
        professional_education_units: '',
        exception_basis: '',
        provisional_until: '',
        notes: '',
        retained_documents: [] as string[],
        new_documents: [] as File[],
    });

    const activeFilterCount = useMemo(() => {
        let count = 0;
        if (statusFilter !== 'all') count++;
        return count;
    }, [statusFilter]);

    const filteredTeachers = useMemo(() => {
        return teachers.filter((t) => {
            const matchesSearch = t.name.toLowerCase().includes(searchQuery.toLowerCase()) || 
                                 t.email.toLowerCase().includes(searchQuery.toLowerCase());
            const matchesStatus = statusFilter === 'all' || t.profile.qualification_status === statusFilter;
            return matchesSearch && matchesStatus;
        });
    }, [teachers, searchQuery, statusFilter]);

    const searchSuggestions = useMemo(
        () =>
            teachers.map((t) => ({
                id: t.id,
                label: t.name,
                value: t.name,
                description: t.email,
            })),
        [teachers],
    );

    const handleResetFilters = () => {
        setSearchQuery('');
        setStatusFilter('all');
    };

    const openEdit = (teacher: Teacher) => {
        setSelectedTeacher(teacher);
        form.setData({
            _method: 'patch',
            qualification_status: teacher.profile.qualification_status,
            is_let_passer: teacher.profile.is_let_passer,
            prc_license_no: teacher.profile.prc_license_no ?? '',
            license_valid_until: teacher.profile.license_valid_until ?? '',
            degree: teacher.profile.degree ?? '',
            major: teacher.profile.major ?? '',
            professional_education_units:
                teacher.profile.professional_education_units !== null
                    ? String(teacher.profile.professional_education_units)
                    : '',
            exception_basis: teacher.profile.exception_basis ?? '',
            provisional_until: teacher.profile.provisional_until ?? '',
            notes: teacher.profile.notes ?? '',
            retained_documents: teacher.profile.eligibility_documents || [],
            new_documents: [],
        });
        setIsEditOpen(true);
    };

    const submit = () => {
        if (!selectedTeacher) return;

        const unitsRaw = form.data.professional_education_units.trim();
        const parsedUnits = unitsRaw === '' ? null : Number(unitsRaw);

        form.transform((data) => ({
            ...data,
            professional_education_units:
                parsedUnits !== null && Number.isFinite(parsedUnits)
                    ? parsedUnits
                    : null,
            prc_license_no: data.prc_license_no.trim() || null,
            license_valid_until: data.license_valid_until || null,
            degree: data.degree.trim() || null,
            major: data.major.trim() || null,
            exception_basis: data.exception_basis.trim() || null,
            provisional_until: data.provisional_until || null,
            notes: data.notes.trim() || null,
        }));

        form.post(upsert({ user: selectedTeacher.id }).url, {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                setIsEditOpen(false);
                setSelectedTeacher(null);
            },
        });
    };

    const summary = useMemo(() => {
        return teachers.reduce(
            (acc, teacher) => {
                const status = teacher.profile.qualification_status;
                if (status === 'fully_qualified') acc.fully += 1;
                if (status === 'provisionally_qualified') acc.provisional += 1;
                if (status === 'not_qualified') acc.notQualified += 1;
                return acc;
            },
            { fully: 0, provisional: 0, notQualified: 0 },
        );
    }, [teachers]);

    const statusLabel = (status: TeacherProfilePayload['qualification_status']) => {
        if (status === 'fully_qualified') return 'Fully Qualified';
        if (status === 'provisionally_qualified') return 'Provisional';
        return 'Not Qualified';
    };

    const statusClass = (status: TeacherProfilePayload['qualification_status']) => {
        if (status === 'fully_qualified') {
            return 'bg-emerald-500/15 text-emerald-700 border-emerald-200 dark:text-emerald-400 dark:border-emerald-800';
        }
        if (status === 'provisionally_qualified') {
            return 'bg-amber-500/15 text-amber-700 border-amber-200 dark:text-amber-400 dark:border-amber-800';
        }
        return 'bg-slate-500/10 text-slate-700 border-slate-200 dark:text-slate-300 dark:border-slate-700';
    };

    const triggerExport = (format: 'xlsx' | 'csv' | 'pdf') => {
        // Assume export logic exists or uses window.location
        const params = new URLSearchParams({
            format,
            search: searchQuery,
            status: statusFilter,
        });
        window.location.assign(`/admin/teacher-profiles/export?${params.toString()}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Teacher Profiles" />
            <div className="flex flex-col gap-6">
                <div className="grid gap-4 md:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center justify-between p-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Fully Qualified
                                </p>
                                <p className="text-2xl font-semibold">
                                    {summary.fully}
                                </p>
                            </div>
                            <ShieldCheck className="size-5 text-emerald-500" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center justify-between p-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Provisional
                                </p>
                                <p className="text-2xl font-semibold">
                                    {summary.provisional}
                                </p>
                            </div>
                            <UserCheck className="size-5 text-amber-500" />
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center justify-between p-5">
                            <div>
                                <p className="text-xs text-muted-foreground">
                                    Not Qualified
                                </p>
                                <p className="text-2xl font-semibold">
                                    {summary.notQualified}
                                </p>
                            </div>
                            <UserCheck className="size-5 text-slate-500" />
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader className="border-b">
                        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <CardTitle>Faculty Profiles</CardTitle>
                            <div className="flex flex-wrap items-center gap-2">
                                <SearchAutocompleteInput
                                    placeholder="Search by name or email..."
                                    wrapperClassName="w-full sm:max-w-xs"
                                    value={searchQuery}
                                    onValueChange={setSearchQuery}
                                    suggestions={searchSuggestions}
                                />
                                
                                <Popover>
                                    <PopoverTrigger asChild>
                                        <Button variant="outline" className="gap-2">
                                            <ListFilter className="size-4" />
                                            Filters
                                            {activeFilterCount > 0 && (
                                                <Badge variant="secondary" className="ml-1 px-1 py-0 text-[10px]">
                                                    {activeFilterCount}
                                                </Badge>
                                            )}
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-64" align="end">
                                        <div className="grid gap-4">
                                            <div className="flex items-center justify-between">
                                                <h4 className="font-medium leading-none">Table Filters</h4>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-auto p-0 text-xs text-muted-foreground"
                                                    onClick={handleResetFilters}
                                                >
                                                    Reset
                                                </Button>
                                            </div>
                                            <Separator />
                                            <div className="grid gap-4">
                                                <div className="grid gap-2">
                                                    <Label>Qualification Status</Label>
                                                    <Select
                                                        value={statusFilter}
                                                        onValueChange={setStatusFilter}
                                                    >
                                                        <SelectTrigger>
                                                            <SelectValue />
                                                        </SelectTrigger>
                                                        <SelectContent>
                                                            <SelectItem value="all">All Statuses</SelectItem>
                                                            <SelectItem value="fully_qualified">Fully Qualified</SelectItem>
                                                            <SelectItem value="provisionally_qualified">Provisional</SelectItem>
                                                            <SelectItem value="not_qualified">Not Qualified</SelectItem>
                                                        </SelectContent>
                                                    </Select>
                                                </div>
                                            </div>
                                        </div>
                                    </PopoverContent>
                                </Popover>

                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline" className="gap-2">
                                            <Download className="size-4" />
                                            Export
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem onClick={() => triggerExport('xlsx')}>
                                            Export as Excel (.xlsx)
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => triggerExport('csv')}>
                                            Export as CSV (.csv)
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => triggerExport('pdf')}>
                                            Export as PDF (.pdf)
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Teacher</TableHead>
                                    <TableHead className="border-l">Account Email</TableHead>
                                    <TableHead className="border-l">Qualification</TableHead>
                                    <TableHead className="border-l">LET / PRC</TableHead>
                                    <TableHead className="border-l pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {filteredTeachers.map((teacher) => (
                                    <TableRow key={teacher.id}>
                                        <TableCell className="pl-6 font-medium">
                                            {teacher.name}
                                        </TableCell>
                                        <TableCell className="border-l text-muted-foreground">
                                            {teacher.email}
                                        </TableCell>
                                        <TableCell className="border-l">
                                            <Badge
                                                variant="outline"
                                                className={statusClass(
                                                    teacher.profile
                                                        .qualification_status,
                                                )}
                                            >
                                                {statusLabel(
                                                    teacher.profile
                                                        .qualification_status,
                                                )}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="border-l text-sm text-muted-foreground">
                                            {teacher.profile.is_let_passer
                                                ? `LET Passer${teacher.profile.prc_license_no ? ` • ${teacher.profile.prc_license_no}` : ''}`
                                                : 'Not tagged'}
                                        </TableCell>
                                        <TableCell className="border-l pr-6 text-right">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                className="gap-2"
                                                onClick={() => openEdit(teacher)}
                                            >
                                                <Edit2 className="size-3.5" />
                                                Edit Profile
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                {filteredTeachers.length === 0 && (
                                    <TableRow>
                                        <TableCell colSpan={5} className="h-24 text-center text-sm text-muted-foreground">
                                            No matching faculty profiles found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={isEditOpen} onOpenChange={setIsEditOpen}>
                <DialogContent className="sm:max-w-[640px]">
                    <DialogHeader>
                        <DialogTitle>Teacher Qualification Profile</DialogTitle>
                        <DialogDescription>
                            Update qualification data for{' '}
                            <span className="font-medium text-foreground">
                                {selectedTeacher?.name}
                            </span>
                            .
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>Qualification Status</Label>
                                <Select
                                    value={form.data.qualification_status}
                                    onValueChange={(value) =>
                                        form.setData(
                                            'qualification_status',
                                            value as
                                                | 'fully_qualified'
                                                | 'provisionally_qualified'
                                                | 'not_qualified',
                                        )
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="fully_qualified">
                                            Fully Qualified
                                        </SelectItem>
                                        <SelectItem value="provisionally_qualified">
                                            Provisionally Qualified
                                        </SelectItem>
                                        <SelectItem value="not_qualified">
                                            Not Qualified
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="grid gap-2">
                                <Label>LET Passer</Label>
                                <Select
                                    value={form.data.is_let_passer ? 'yes' : 'no'}
                                    onValueChange={(value) =>
                                        form.setData('is_let_passer', value === 'yes')
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="yes">Yes</SelectItem>
                                        <SelectItem value="no">No</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>PRC License No.</Label>
                                <Input
                                    value={form.data.prc_license_no}
                                    onChange={(e) =>
                                        form.setData('prc_license_no', e.target.value)
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>License Valid Until</Label>
                                <Input
                                    type="date"
                                    value={form.data.license_valid_until}
                                    onChange={(e) =>
                                        form.setData(
                                            'license_valid_until',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>Degree</Label>
                                <Input
                                    value={form.data.degree}
                                    onChange={(e) =>
                                        form.setData('degree', e.target.value)
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Major / Specialization</Label>
                                <Input
                                    value={form.data.major}
                                    onChange={(e) =>
                                        form.setData('major', e.target.value)
                                    }
                                />
                            </div>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="grid gap-2">
                                <Label>Professional Education Units</Label>
                                <Input
                                    type="number"
                                    min={0}
                                    value={form.data.professional_education_units}
                                    onChange={(e) =>
                                        form.setData(
                                            'professional_education_units',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={
                                        form.errors.professional_education_units
                                    }
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label>Provisional Until</Label>
                                <Input
                                    type="date"
                                    value={form.data.provisional_until}
                                    onChange={(e) =>
                                        form.setData(
                                            'provisional_until',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError
                                    message={form.errors.provisional_until}
                                />
                            </div>
                        </div>

                        <div className="grid gap-2">
                            <Label>Exception Basis</Label>
                            <Input
                                value={form.data.exception_basis}
                                onChange={(e) =>
                                    form.setData('exception_basis', e.target.value)
                                }
                            />
                            <InputError message={form.errors.exception_basis} />
                        </div>

                        <div className="grid gap-2">
                            <Label>Notes</Label>
                            <Textarea
                                value={form.data.notes}
                                onChange={(e) => form.setData('notes', e.target.value)}
                                className="min-h-[96px]"
                            />
                            <InputError message={form.errors.notes} />
                        </div>

                        <div className="grid gap-2">
                            <Label>Eligibility Documents (Certificates/Proof)</Label>
                            <Input
                                type="file"
                                multiple
                                accept=".pdf,.jpg,.jpeg,.png"
                                onChange={(e) => {
                                    if (e.target.files) {
                                        form.setData('new_documents', Array.from(e.target.files));
                                    }
                                }}
                            />
                            {form.data.retained_documents.length > 0 && (
                                <div className="mt-2 text-sm text-muted-foreground">
                                    <p className="mb-1 font-medium text-foreground">Existing Documents:</p>
                                    <ul className="list-inside list-disc pl-4">
                                        {form.data.retained_documents.map((doc, idx) => (
                                            <li key={idx} className="flex items-center gap-2">
                                                <button
                                                    type="button"
                                                    className="text-left hover:underline"
                                                    onClick={() => {
                                                        if (!selectedTeacher) {
                                                            return;
                                                        }
                                                        const params = new URLSearchParams({
                                                            path: doc,
                                                        });
                                                        window.location.assign(
                                                            `/admin/teacher-profiles/${selectedTeacher.id}/documents/download?${params.toString()}`,
                                                        );
                                                    }}
                                                >
                                                    {doc.split('/').pop()}
                                                </button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    className="h-auto p-1 text-red-500 hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/50"
                                                    onClick={() => {
                                                        form.setData(
                                                            'retained_documents',
                                                            form.data.retained_documents.filter((_, i) => i !== idx)
                                                        );
                                                    }}
                                                >
                                                    Remove
                                                </Button>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
                            <InputError message={form.errors.new_documents} />
                            <InputError message={form.errors.retained_documents} />
                        </div>
                        <InputError message={form.errors.qualification_status} />
                        <InputError message={form.errors.is_let_passer} />
                        <InputError message={form.errors.prc_license_no} />
                        <InputError message={form.errors.license_valid_until} />
                        <InputError message={form.errors.degree} />
                        <InputError message={form.errors.major} />
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => {
                                setIsEditOpen(false);
                                setSelectedTeacher(null);
                            }}
                        >
                            Cancel
                        </Button>
                        <Button onClick={submit} disabled={form.processing}>
                            Save Profile
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
