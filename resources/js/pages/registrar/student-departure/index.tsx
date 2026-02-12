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
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Student Departure',
        href: '/registrar/student-departure',
    },
];

export default function StudentDeparture() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Student Departure" />
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
                            <CardContent className="grid grid-cols-11 gap-6">
                                <div className="col-span-4 space-y-6">
                                    <Card className="max-w-full">
                                        <CardContent className="gap-4 space-y-4">
                                            <div className="flex flex-col items-center gap-2">
                                                <Avatar size="2xl">
                                                    <AvatarImage src="" />
                                                    <AvatarFallback>
                                                        J
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <h4 className="text-center">
                                                        Juan Dela Cruz
                                                    </h4>
                                                    <p className="text-center">
                                                        {' '}
                                                        Grade 7 | LRN:
                                                        1234567890123
                                                    </p>
                                                </div>
                                            </div>
                                            <Separator />
                                            <div className="space-y-2">
                                                <div className="flex flex-row justify-between">
                                                    <p>Grade Level:</p>
                                                    <p>Grade 7</p>
                                                </div>
                                                <div className="flex flex-row justify-between">
                                                    <p>Section:</p>
                                                    <p>Section A</p>
                                                </div>
                                                <div className="flex flex-row justify-between">
                                                    <p>Status:</p>
                                                    <p>Active</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                    <Card className="">
                                        <CardContent>
                                            <h4>Warning</h4>
                                            <span className="text-xs">
                                                This action will remove the
                                                student from all Active Class
                                                Lists. Financial and Academic
                                                records will be retained for
                                                history.
                                            </span>
                                        </CardContent>
                                    </Card>
                                </div>
                                <Card className="col-span-7">
                                    <CardContent>
                                        <div className="mb-4 space-y-4">
                                            <h4>Departure Details:</h4>
                                            <Separator />
                                        </div>
                                        <FieldGroup>
                                            <FieldSet>
                                                <FieldGroup className="flex flex-row">
                                                    <Field>
                                                        <FieldLabel>
                                                            Reason for Leaving:
                                                        </FieldLabel>
                                                        <Select>
                                                            <SelectTrigger></SelectTrigger>
                                                        </Select>
                                                    </Field>
                                                    <Field>
                                                        <FieldLabel>
                                                            Effectivity Date:
                                                        </FieldLabel>
                                                        <Input
                                                            type="text"
                                                            placeholder="mm/dd/yyyy"
                                                        />
                                                    </Field>
                                                </FieldGroup>
                                                <Card>
                                                    <CardContent>
                                                        <FieldGroup>
                                                            <FieldLabel>
                                                                Transfer
                                                                Credentials
                                                                Released?
                                                            </FieldLabel>
                                                        </FieldGroup>
                                                    </CardContent>
                                                </Card>
                                                <Field>
                                                    <FieldLabel>
                                                        Remarks:
                                                    </FieldLabel>
                                                    <Textarea />
                                                </Field>
                                                <Button>
                                                    Process Departure
                                                </Button>
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
