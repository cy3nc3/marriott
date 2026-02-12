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
import { ButtonGroup } from '@/components/ui/button-group';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Permanent Records',
        href: '/registrar/permanent-records',
    },
];

export default function PermanentRecords() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Permanent Records" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Card>
                    <CardContent className="space-y-6">
                        <Field>
                            <FieldLabel>Search Student</FieldLabel>
                            <ButtonGroup>
                                <Input
                                    id="search-student"
                                    type="string"
                                    placeholder="Enter name or LRN"
                                />
                                <Button variant="outline">Search</Button>
                            </ButtonGroup>
                        </Field>
                        <Card>
                            <CardContent className="flex flex-row items-center justify-between">
                                <div className="flex flex-row items-center gap-4">
                                    <Avatar size="2xl">
                                        <AvatarImage src="" />
                                        <AvatarFallback>J</AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <h4>Juan Dela Cruz</h4>
                                        <p> Grade 7 | LRN: 1234567890123</p>
                                    </div>
                                </div>
                                <Button variant="outline" size="lg">
                                    Print SF10
                                </Button>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>Academic History</CardHeader>
                            <CardContent className="grid grid-cols-5 items-start gap-6">
                                <div className="col-span-3 space-y-6">
                                    <Card>
                                        <CardHeader>
                                            <h4>Grade 7</h4>
                                            <p>School Year | School Name</p>
                                        </CardHeader>
                                        <CardContent>
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>
                                                            Subject
                                                        </TableHead>
                                                        <TableHead className="text-right">
                                                            Grade
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    <TableRow>
                                                        <TableCell>
                                                            Math
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            90
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            English
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            91
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            Science
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            92
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            AP
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            93
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            Filipino
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            94
                                                        </TableCell>
                                                    </TableRow>
                                                </TableBody>
                                            </Table>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader>
                                            <h4>Grade 8</h4>
                                            <p>School Year | School Name</p>
                                        </CardHeader>
                                        <CardContent>
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>
                                                            Subject
                                                        </TableHead>
                                                        <TableHead className="text-right">
                                                            Grade
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    <TableRow>
                                                        <TableCell>
                                                            Math
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            90
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            English
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            91
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            Science
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            92
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            AP
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            93
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            Filipino
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            94
                                                        </TableCell>
                                                    </TableRow>
                                                </TableBody>
                                            </Table>
                                        </CardContent>
                                    </Card>

                                    <Card>
                                        <CardHeader>
                                            <h4>Grade 9</h4>
                                            <p>School Year | School Name</p>
                                        </CardHeader>
                                        <CardContent>
                                            <Table>
                                                <TableHeader>
                                                    <TableRow>
                                                        <TableHead>
                                                            Subject
                                                        </TableHead>
                                                        <TableHead className="text-right">
                                                            Grade
                                                        </TableHead>
                                                    </TableRow>
                                                </TableHeader>
                                                <TableBody>
                                                    <TableRow>
                                                        <TableCell>
                                                            Math
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            90
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            English
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            91
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            Science
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            92
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            AP
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            93
                                                        </TableCell>
                                                    </TableRow>
                                                    <TableRow>
                                                        <TableCell>
                                                            Filipino
                                                        </TableCell>
                                                        <TableCell className="text-right">
                                                            94
                                                        </TableCell>
                                                    </TableRow>
                                                </TableBody>
                                            </Table>
                                        </CardContent>
                                    </Card>
                                </div>
                                <Card className="sticky top-4 col-span-2 gap-2">
                                    <CardHeader className="gap-0">
                                        Add Historical Record
                                    </CardHeader>
                                    <CardContent className="">
                                        <FieldGroup className="gap-4">
                                            <FieldSet className="gap-4">
                                                <FieldGroup className="gap-4">
                                                    <Field className="py-0">
                                                        <FieldLabel htmlFor="school-name">
                                                            School Name
                                                        </FieldLabel>
                                                        <Input
                                                            id="school-name"
                                                            type="text"
                                                            placeholder="Enter Scool Name"
                                                        />
                                                    </Field>
                                                    <Field
                                                        orientation="horizontal"
                                                        className="flex items-center justify-between"
                                                    >
                                                        <FieldLabel className="whitespace-nowrap">
                                                            School Year
                                                        </FieldLabel>
                                                        <div className="flex items-center gap-2">
                                                            <Input
                                                                id="sy-from"
                                                                type="text"
                                                                placeholder="20XX"
                                                                className="w-20"
                                                            />
                                                            <span className="text-muted-foreground">
                                                                -
                                                            </span>
                                                            <Input
                                                                id="sy-to"
                                                                type="text"
                                                                placeholder="20XX"
                                                                className="w-20"
                                                            />
                                                        </div>
                                                    </Field>
                                                    <FieldGroup className="gap-4">
                                                        <Field
                                                            orientation={
                                                                'horizontal'
                                                            }
                                                        >
                                                            <FieldLabel>
                                                                Grade Level:
                                                            </FieldLabel>
                                                            <Input
                                                                id="grade-level"
                                                                type="text"
                                                                placeholder="7"
                                                                className="max-w-xs"
                                                            />
                                                        </Field>
                                                    </FieldGroup>

                                                    <FieldSeparator />
                                                    <Field className="gap-4">
                                                        <FieldLabel>
                                                            Subjects & Grades
                                                        </FieldLabel>
                                                        <div className="grid grid-cols-2 gap-4">
                                                            <Input
                                                                type="text"
                                                                placeholder="Subject"
                                                            />
                                                            <Input
                                                                type="text"
                                                                placeholder="Grade"
                                                            />
                                                        </div>
                                                    </Field>

                                                    <Button
                                                        variant="ghost"
                                                        size="xs"
                                                        className="max-w-fit self-center"
                                                    >
                                                        + Add Subject
                                                    </Button>
                                                    <Button>Save</Button>
                                                </FieldGroup>
                                            </FieldSet>
                                        </FieldGroup>
                                    </CardContent>
                                </Card>
                            </CardContent>
                        </Card>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
