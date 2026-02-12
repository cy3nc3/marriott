import { useState } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
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
import { Plus, UserPlus, Info } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from "@/components/ui/dialog"
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select"
import { Label } from "@/components/ui/label"
import { Input } from "@/components/ui/input"

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Discount Manager',
        href: '/finance/discount-manager',
    },
];

export default function DiscountManager() {
    const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
    const [isTagModalOpen, setIsTagModalOpen] = useState(false);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Discount Manager" />
            <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4 lg:p-6">
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    {/* Section 1: Discount Types */}
                    <Card className="lg:col-span-1">
                        <CardHeader className="flex flex-row items-center justify-between bg-muted/30 border-b space-y-0">
                            <CardTitle className="text-lg">Discount Types</CardTitle>
                            <Button variant="ghost" size="icon" onClick={() => setIsCreateModalOpen(true)}>
                                <Plus className="size-5" />
                            </Button>
                        </CardHeader>
                        <CardContent className="p-4 space-y-4">
                            <div className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/30 transition-colors">
                                <div>
                                    <h4 className="font-bold">Academic Scholarship</h4>
                                    <span className="text-[10px] font-bold uppercase text-muted-foreground">Percentage</span>
                                </div>
                                <div className="text-green-600 font-bold">100%</div>
                            </div>
                            <div className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/30 transition-colors">
                                <div>
                                    <h4 className="font-bold">Sibling Discount</h4>
                                    <span className="text-[10px] font-bold uppercase text-muted-foreground">Percentage</span>
                                </div>
                                <div className="text-green-600 font-bold">10%</div>
                            </div>
                            <div className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/30 transition-colors">
                                <div>
                                    <h4 className="font-bold">Early Bird</h4>
                                    <span className="text-[10px] font-bold uppercase text-muted-foreground">Fixed</span>
                                </div>
                                <div className="text-green-600 font-bold">₱ 1,000.00</div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Section 2: Scholar List */}
                    <Card className="lg:col-span-2">
                        <CardHeader className="flex flex-row items-center justify-between bg-muted/30 border-b space-y-0">
                            <CardTitle className="text-lg">Scholar List</CardTitle>
                            <Button size="sm" className="gap-1" onClick={() => setIsTagModalOpen(true)}>
                                <UserPlus className="size-4" />
                                Tag Student
                            </Button>
                        </CardHeader>
                        <CardContent className="p-0">
                            <Table>
                                <TableHeader className="bg-muted/20">
                                    <TableRow>
                                        <TableHead className="pl-6">Student Name</TableHead>
                                        <TableHead>Grade</TableHead>
                                        <TableHead>Discount</TableHead>
                                        <TableHead className="text-right pr-6">Amount Deducted</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell className="pl-6 font-medium">Juan Dela Cruz</TableCell>
                                        <TableCell>Grade 7</TableCell>
                                        <TableCell className="text-primary font-bold">Academic Scholarship</TableCell>
                                        <TableCell className="text-right pr-6 font-mono font-bold">20,000.00</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="pl-6 font-medium">Maria Santos</TableCell>
                                        <TableCell>Grade 8</TableCell>
                                        <TableCell className="text-primary font-bold">Sibling Discount</TableCell>
                                        <TableCell className="text-right pr-6 font-mono font-bold">2,200.00</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                </div>

                {/* Create Discount Modal */}
                <Dialog open={isCreateModalOpen} onOpenChange={setIsCreateModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Create New Discount</DialogTitle>
                            <DialogDescription>Add a new discount type to the system.</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="discount-name">Name</Label>
                                <Input id="discount-name" placeholder="eg. Loyalty Award" />
                            </div>
                            <div className="grid grid-cols-2 gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="discount-type">Type</Label>
                                    <Select defaultValue="Percentage">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="Percentage">Percentage (%)</SelectItem>
                                            <SelectItem value="Fixed">Fixed Amount (₱)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="discount-value">Value</Label>
                                    <Input id="discount-value" type="number" step="0.01" />
                                </div>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsCreateModalOpen(false)}>Cancel</Button>
                            <Button onClick={() => setIsCreateModalOpen(false)}>Create</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                {/* Tag Student Modal */}
                <Dialog open={isTagModalOpen} onOpenChange={setIsTagModalOpen}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Tag Student</DialogTitle>
                            <DialogDescription>Apply a discount to a specific student.</DialogDescription>
                        </DialogHeader>
                        <div className="grid gap-4 py-4">
                            <div className="grid gap-2">
                                <Label htmlFor="student-name">Student Name</Label>
                                <Input id="student-name" placeholder="Search student..." />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="select-discount">Select Discount</Label>
                                <Select>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a discount" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="1">Academic Scholarship (100%)</SelectItem>
                                        <SelectItem value="2">Sibling Discount (10%)</SelectItem>
                                        <SelectItem value="3">Early Bird (₱ 1,000.00)</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setIsTagModalOpen(false)}>Cancel</Button>
                            <Button onClick={() => setIsTagModalOpen(false)}>Tag Student</Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
