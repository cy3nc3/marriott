import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Plus } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Grading Sheet',
        href: '/teacher/grading-sheet',
    },
];

export default function GradingSheet() {
    const [isAddModalOpen, setIsAddModalOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Grading Sheet" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <Card className="overflow-hidden">
                    {/* Control Bar */}
                    <div className="p-6 border-b bg-muted/30 flex flex-col md:flex-row justify-between items-end gap-4">
                        <div className="flex flex-wrap gap-4 items-center w-full md:w-auto">
                            <div className="space-y-1.5">
                                <Label className="text-[10px] font-bold uppercase text-muted-foreground tracking-wider">Quarter</Label>
                                <Select defaultValue="1st">
                                    <SelectTrigger className="w-32 h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1st">1st</SelectItem>
                                        <SelectItem value="2nd">2nd</SelectItem>
                                        <SelectItem value="3rd">3rd</SelectItem>
                                        <SelectItem value="4th">4th</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-[10px] font-bold uppercase text-muted-foreground tracking-wider">Grade</Label>
                                <Select defaultValue="7">
                                    <SelectTrigger className="w-32 h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="7">Grade 7</SelectItem>
                                        <SelectItem value="8">Grade 8</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-[10px] font-bold uppercase text-muted-foreground tracking-wider">Section</Label>
                                <Select defaultValue="rizal">
                                    <SelectTrigger className="w-40 h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="rizal">Rizal</SelectItem>
                                        <SelectItem value="bonifacio">Bonifacio</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-1.5">
                                <Label className="text-[10px] font-bold uppercase text-muted-foreground tracking-wider">Subject</Label>
                                <Select defaultValue="math">
                                    <SelectTrigger className="w-40 h-9">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="math">Math</SelectItem>
                                        <SelectItem value="science">Science</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <Dialog open={isAddModalOpen} onOpenChange={setIsAddModalOpen}>
                            <DialogTrigger asChild>
                                <Button className="gap-2">
                                    <Plus className="size-4" />
                                    Add Entry
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Add New Activity</DialogTitle>
                                    <DialogDescription>Create a new graded entry for this class.</DialogDescription>
                                </DialogHeader>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="title">Activity Title</Label>
                                        <Input id="title" placeholder="e.g., Quiz 3" />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="type">Type</Label>
                                        <Select defaultValue="ww">
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="ww">Written Work (WW)</SelectItem>
                                                <SelectItem value="pt">Performance Task (PT)</SelectItem>
                                                <SelectItem value="qa">Quarterly Assessment (QA)</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="max">Max Points</Label>
                                        <Input id="max" type="number" min="1" />
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button variant="outline" onClick={() => setIsAddModalOpen(false)}>Cancel</Button>
                                    <Button onClick={() => setIsAddModalOpen(false)}>Save Activity</Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>

                    <CardContent className="p-0 overflow-auto">
                        <Table className="border-collapse">
                            <TableHeader>
                                <TableRow>
                                    <TableHead rowSpan={2} className="sticky left-0 bg-muted/50 z-30 border-r min-w-[200px] text-center font-bold uppercase text-[10px]">Student Name</TableHead>
                                    <TableHead colSpan={2} className="text-center font-bold text-white bg-blue-600 border-l border-white/20 uppercase text-[10px]">Written Works (40%)</TableHead>
                                    <TableHead colSpan={2} className="text-center font-bold text-white bg-green-600 border-l border-white/20 uppercase text-[10px]">Performance Tasks (40%)</TableHead>
                                    <TableHead colSpan={1} className="text-center font-bold text-white bg-purple-600 border-l border-white/20 uppercase text-[10px]">Quarterly Assessment (20%)</TableHead>
                                    <TableHead rowSpan={2} className="sticky right-0 bg-primary/10 z-30 border-l min-w-[100px] text-center font-bold uppercase text-[10px]">Initial Grade</TableHead>
                                </TableRow>
                                <TableRow>
                                    {/* WW */}
                                    <TableHead className="bg-blue-50 text-center border-r min-w-[100px]">
                                        <div className="flex flex-col text-[10px]">
                                            <span className="font-bold">Quiz 1</span>
                                            <span className="text-muted-foreground">(20 pts)</span>
                                        </div>
                                    </TableHead>
                                    <TableHead className="bg-blue-50 text-center border-r min-w-[100px]">
                                        <div className="flex flex-col text-[10px]">
                                            <span className="font-bold">Quiz 2</span>
                                            <span className="text-muted-foreground">(30 pts)</span>
                                        </div>
                                    </TableHead>
                                    {/* PT */}
                                    <TableHead className="bg-green-50 text-center border-r min-w-[100px]">
                                        <div className="flex flex-col text-[10px]">
                                            <span className="font-bold">Project 1</span>
                                            <span className="text-muted-foreground">(50 pts)</span>
                                        </div>
                                    </TableHead>
                                    <TableHead className="bg-green-50 text-center border-r min-w-[100px]">
                                        <div className="flex flex-col text-[10px]">
                                            <span className="font-bold">Recitation</span>
                                            <span className="text-muted-foreground">(20 pts)</span>
                                        </div>
                                    </TableHead>
                                    {/* QA */}
                                    <TableHead className="bg-purple-50 text-center border-r min-w-[100px]">
                                        <div className="flex flex-col text-[10px]">
                                            <span className="font-bold">Exam</span>
                                            <span className="text-muted-foreground">(100 pts)</span>
                                        </div>
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow className="hover:bg-muted/30">
                                    <TableCell className="sticky left-0 bg-background z-20 border-r font-medium">Dela Cruz, Juan</TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="18" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="25" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="45" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="18" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="85" /></TableCell>
                                    <TableCell className="sticky right-0 bg-primary/5 z-20 border-l text-center font-bold text-primary">86.60</TableCell>
                                </TableRow>
                                <TableRow className="hover:bg-muted/30">
                                    <TableCell className="sticky left-0 bg-background z-20 border-r font-medium">Santos, Maria</TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="20" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="28" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="48" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="20" /></TableCell>
                                    <TableCell className="text-center"><Input type="number" className="w-16 h-8 text-center mx-auto" defaultValue="92" /></TableCell>
                                    <TableCell className="sticky right-0 bg-primary/5 z-20 border-l text-center font-bold text-primary">90.60</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
