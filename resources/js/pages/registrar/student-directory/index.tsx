import { Head } from '@inertiajs/react';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Field,
    FieldContent,
    FieldDescription,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldLegend,
    FieldSeparator,
    FieldSet,
    FieldTitle,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
    SelectLabel,
} from '@/components/ui/select';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Directory',
        href: '/registrar/student-directory',
    },
];

export default function StudentDirectory() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Directory" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardContent>
                        <FieldGroup className="flex flex-row justify-start gap-6 space-y-6">
                            <Field className="max-w-3xs">
                                <FieldLabel>Search Student</FieldLabel>
                                <Input
                                    id="search-student"
                                    type="text"
                                    placeholder="Search by name or LRN"
                                />
                            </Field>
                            <Field className="max-w-fit">
                                <FieldLabel>Grade Level</FieldLabel>
                                <Select>
                                    <SelectTrigger className="max-w-fit">
                                        <SelectValue placeholder="Select Grade Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>
                                                Grade Level
                                            </SelectLabel>
                                            <SelectItem value="grade-7">
                                                Grade 7
                                            </SelectItem>
                                            <SelectItem value="grade-8">
                                                Grade 8
                                            </SelectItem>
                                            <SelectItem value="grade-9">
                                                Grade 9
                                            </SelectItem>
                                            <SelectItem value="grade-10">
                                                Grade 10
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </Field>
                            <Field className="max-w-fit">
                                <FieldLabel>Section</FieldLabel>
                                <Select>
                                    <SelectTrigger className="max-w-fit">
                                        <SelectValue placeholder="Select Section" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel>Section</SelectLabel>
                                            <SelectItem value="section-a">
                                                Section A
                                            </SelectItem>
                                            <SelectItem value="section-b">
                                                Section B
                                            </SelectItem>
                                            <SelectItem value="section-c">
                                                Section C
                                            </SelectItem>
                                            <SelectItem value="section-d">
                                                Section D
                                            </SelectItem>
                                        </SelectGroup>
                                    </SelectContent>
                                </Select>
                            </Field>
                        </FieldGroup>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>LRN</TableHead>
                                    <TableHead className="text-center">
                                        Student Name
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Grade & Section
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Emergency Contact
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Requirements
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        1000000000001
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Godalle, Jade
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Grade 7 - Section A
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Atan Godalle
                                        <br />
                                        09123456789
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Badge Here
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Action Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        1000000000002
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Solitario, Edson
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Grade 8 - Section B
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Sadam Solitario
                                        <br />
                                        0912345671
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Badge Here
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Action Here
                                    </TableCell>
                                </TableRow>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        1000000000003
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Raagas, Francis
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Grade 9 - Section C
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Bandol Raagas
                                        <br />
                                        09123456782
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Badge Here
                                    </TableCell>
                                    <TableCell className="text-right">
                                        Action Here
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
