import { Head } from '@inertiajs/react';
import { CalendarDays, Clock, Printer } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Schedule',
        href: '/parent/schedule',
    },
];

const START_HOUR = 7;
const END_HOUR = 17;
const HOUR_HEIGHT = 72;
const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

type ScheduleItem = {
    day: string;
    start: string;
    end: string;
    title: string;
    teacher: string;
    type: 'class' | 'advisory';
};

type BreakItem = {
    label: string;
    start: string;
    end: string;
};

interface Props {
    student_name: string | null;
    schedule_items: ScheduleItem[];
    break_items: BreakItem[];
}

export default function Schedule({
    student_name,
    schedule_items,
    break_items,
}: Props) {

    const timeToMinutes = (time: string) => {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    };

    const getPosition = (time: string) =>
        ((timeToMinutes(time) - START_HOUR * 60) / 60) * HOUR_HEIGHT;

    const getHeight = (start: string, end: string) =>
        ((timeToMinutes(end) - timeToMinutes(start)) / 60) * HOUR_HEIGHT;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Class Schedule" />

            <div className="flex flex-col gap-6">
                <Card>
                    <CardHeader className="border-b">
                        <div className="flex items-center justify-between gap-3">
                            <div className="flex items-center gap-2">
                                <CalendarDays className="size-4 text-muted-foreground" />
                                <CardTitle>Weekly Class Schedule</CardTitle>
                            </div>
                            <div className="flex items-center gap-2">
                                {student_name && (
                                    <Badge variant="secondary">
                                        {student_name}
                                    </Badge>
                                )}
                                <Badge variant="outline">Read-only</Badge>
                                <Button variant="outline">
                                    <Printer className="size-4" />
                                    Print Schedule
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="p-0">
                        <div
                            className="relative flex w-full"
                            style={{
                                height:
                                    (END_HOUR - START_HOUR) * HOUR_HEIGHT + 40,
                            }}
                        >
                            <div className="sticky left-0 z-30 w-20 shrink-0 border-r bg-background pt-10 pl-0.5">
                                {Array.from({
                                    length: END_HOUR - START_HOUR + 1,
                                }).map((_, index) => (
                                    <div
                                        key={index}
                                        className="relative pr-2 text-right"
                                        style={{
                                            height:
                                                index === END_HOUR - START_HOUR
                                                    ? 0
                                                    : HOUR_HEIGHT,
                                        }}
                                    >
                                        <span className="absolute top-0 right-2 -translate-y-1/2 font-mono text-[10px] leading-none font-medium whitespace-nowrap text-muted-foreground uppercase">
                                            {`${(START_HOUR + index) % 12 || 12}:00 ${START_HOUR + index >= 12 ? 'PM' : 'AM'}`}
                                        </span>
                                    </div>
                                ))}
                            </div>

                            <div className="relative min-w-[800px] flex-1">
                                <div className="sticky top-0 z-40 flex h-10 border-b bg-background">
                                    {DAYS.map((day) => (
                                        <div
                                            key={day}
                                            className="flex flex-1 items-center justify-center border-r text-xs font-semibold tracking-wider text-muted-foreground uppercase last:border-r-0"
                                        >
                                            {day}
                                        </div>
                                    ))}
                                </div>

                                <div className="pointer-events-none absolute inset-0 z-0 pt-10">
                                    {Array.from({
                                        length: END_HOUR - START_HOUR,
                                    }).map((_, index) => (
                                        <div
                                            key={index}
                                            className="border-b border-dashed border-border/40"
                                            style={{ height: HOUR_HEIGHT }}
                                        />
                                    ))}
                                </div>

                                <div className="absolute inset-0 z-10 flex pt-10">
                                    {DAYS.map((day) => (
                                        <div
                                            key={day}
                                            className="relative flex-1 border-r last:border-r-0"
                                        >
                                            {break_items.map((breakItem) => (
                                                <div
                                                    key={`${day}-${breakItem.label}`}
                                                    className="absolute inset-x-0 border-y bg-muted/40"
                                                    style={{
                                                        top: getPosition(
                                                            breakItem.start,
                                                        ),
                                                        height: getHeight(
                                                            breakItem.start,
                                                            breakItem.end,
                                                        ),
                                                    }}
                                                >
                                                    <p className="px-1 pt-1 text-center text-[10px] font-semibold text-muted-foreground uppercase">
                                                        {breakItem.label}
                                                    </p>
                                                </div>
                                            ))}

                                            {schedule_items
                                                .filter(
                                                    (item) => item.day === day,
                                                )
                                                .map((item) => (
                                                    <div
                                                        key={`${day}-${item.start}-${item.title}-${item.teacher}`}
                                                        className={cn(
                                                            'absolute right-1 left-1 z-20 rounded-md border px-2 py-1.5 shadow-sm',
                                                            item.type ===
                                                                'advisory'
                                                                ? 'border-amber-300 bg-amber-50'
                                                                : 'border-primary/20 bg-background',
                                                        )}
                                                        style={{
                                                            top: getPosition(
                                                                item.start,
                                                            ),
                                                            height: getHeight(
                                                                item.start,
                                                                item.end,
                                                            ),
                                                        }}
                                                    >
                                                        <div className="flex h-full flex-col justify-between">
                                                            <div className="space-y-0.5">
                                                                <p className="truncate text-[11px] leading-tight font-semibold text-primary">
                                                                    {item.title}
                                                                </p>
                                                                <p className="truncate text-[10px] leading-tight text-muted-foreground">
                                                                    {
                                                                        item.teacher
                                                                    }
                                                                </p>
                                                            </div>
                                                            <div className="mt-1 flex items-center gap-1 text-[10px] text-muted-foreground">
                                                                <Clock className="size-3" />
                                                                <span className="font-mono">
                                                                    {item.start}{' '}
                                                                    - {item.end}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                ))}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
