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
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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
import { SelectLabel } from '@radix-ui/react-select';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Class Lists',
        href: '/admin/class-lists',
    },
];

export default function ClassLists() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Class Lists" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle className="flex max-w-fit flex-row gap-8">
                            <Field>
                                <FieldLabel>Grade Level</FieldLabel>
                                <Select>
                                    <SelectTrigger className="max-w-fit">
                                        <SelectValue placeholder="Select Grade Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel className="px-2 py-1 text-sm text-muted-foreground">
                                                Grade Levels
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
                            <Field>
                                <FieldLabel>Section</FieldLabel>
                                <Select>
                                    <SelectTrigger className="max-w-fit">
                                        <SelectValue placeholder="Select Grade Level" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectGroup>
                                            <SelectLabel className="px-2 py-1 text-sm text-muted-foreground">
                                                Sections
                                            </SelectLabel>
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
                        </CardTitle>
                        <CardDescription></CardDescription>
                        <CardAction>
                            <Button variant="outline">Print Class List</Button>
                        </CardAction>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>LRN</TableHead>
                                    <TableHead className="text-center">
                                        Student Name
                                    </TableHead>
                                    <TableHead className="text-center">
                                        Gender
                                    </TableHead>
                                    {/* <TableHead className="text-center">
                                        Admissions
                                    </TableHead> */}
                                    <TableHead className="text-right">
                                        Status
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow>
                                    <TableCell className="font-medium">
                                        100000000001
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Godalle, Jade
                                    </TableCell>
                                    <TableCell className="text-center">
                                        Male
                                    </TableCell>
                                    {/* <TableCell className="text-center">
                                        Toggle Here
                                    </TableCell> */}
                                    <TableCell className="text-right">
                                        Badge Here
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
