import { format } from 'date-fns';
import { CalendarClock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Label } from '@/components/ui/label';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

type DateTimePickerProps = {
    date?: Date;
    setDate: (date?: Date) => void;
    placeholder?: string;
    className?: string;
};

const hourOptions = Array.from({ length: 12 }, (_, index) => index + 1);
const minuteOptions = Array.from({ length: 60 }, (_, index) => index);

export function DateTimePicker({
    date,
    setDate,
    placeholder = 'Pick date and time',
    className,
}: DateTimePickerProps) {
    const resolveMeridiem = (hours: number): 'AM' | 'PM' => {
        return hours >= 12 ? 'PM' : 'AM';
    };

    const resolveTwelveHour = (hours: number): number => {
        const normalizedHour = hours % 12;

        return normalizedHour === 0 ? 12 : normalizedHour;
    };

    const to24Hour = (hour12: number, meridiem: 'AM' | 'PM'): number => {
        if (meridiem === 'AM') {
            return hour12 === 12 ? 0 : hour12;
        }

        return hour12 === 12 ? 12 : hour12 + 12;
    };

    const selectDate = (selectedDate?: Date) => {
        if (!selectedDate) {
            setDate(undefined);

            return;
        }

        const nextDate = new Date(selectedDate);
        if (date) {
            nextDate.setHours(date.getHours(), date.getMinutes(), 0, 0);
        } else {
            nextDate.setHours(8, 0, 0, 0);
        }

        setDate(nextDate);
    };

    const setHour = (hourValue: string) => {
        if (!date) {
            return;
        }

        const parsedHour = Number(hourValue);
        const meridiem = resolveMeridiem(date.getHours());
        const nextDate = new Date(date);
        nextDate.setHours(
            to24Hour(parsedHour, meridiem),
            nextDate.getMinutes(),
            0,
            0,
        );
        setDate(nextDate);
    };

    const setMinute = (minuteValue: string) => {
        if (!date) {
            return;
        }

        const nextDate = new Date(date);
        nextDate.setHours(nextDate.getHours(), Number(minuteValue), 0, 0);
        setDate(nextDate);
    };

    const setMeridiem = (meridiemValue: string) => {
        if (!date) {
            return;
        }

        const meridiem = meridiemValue === 'PM' ? 'PM' : 'AM';
        const currentHour12 = resolveTwelveHour(date.getHours());
        const nextDate = new Date(date);
        nextDate.setHours(
            to24Hour(currentHour12, meridiem),
            nextDate.getMinutes(),
            0,
            0,
        );
        setDate(nextDate);
    };

    return (
        <Popover>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    className={cn(
                        'w-full justify-start text-left font-normal',
                        !date && 'text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarClock className="mr-2 h-4 w-4" />
                    {date ? format(date, 'PPP p') : <span>{placeholder}</span>}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-auto p-0" align="start">
                <div className="sm:flex">
                    <Calendar
                        mode="single"
                        selected={date}
                        onSelect={selectDate}
                        initialFocus
                    />
                    <div className="border-t p-3 sm:w-48 sm:border-t-0 sm:border-l">
                        <Label className="text-muted-foreground text-xs">
                            Time
                        </Label>
                        <div className="mt-2 grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-2">
                            <div className="min-w-0">
                                <Select
                                    value={
                                        date
                                            ? String(
                                                  resolveTwelveHour(
                                                      date.getHours(),
                                                  ),
                                              )
                                            : ''
                                    }
                                    onValueChange={setHour}
                                    disabled={!date}
                                >
                                    <SelectTrigger className="w-full min-w-0">
                                        <SelectValue placeholder="Hour" />
                                    </SelectTrigger>
                                    <SelectContent className="max-h-56">
                                        {hourOptions.map((hour) => (
                                            <SelectItem
                                                key={hour}
                                                value={String(hour)}
                                            >
                                                {hour}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <span className="text-muted-foreground text-sm font-medium">
                                :
                            </span>
                            <div className="min-w-0">
                                <Select
                                    value={date ? String(date.getMinutes()) : ''}
                                    onValueChange={setMinute}
                                    disabled={!date}
                                >
                                    <SelectTrigger className="w-full min-w-0">
                                        <SelectValue placeholder="Minute" />
                                    </SelectTrigger>
                                    <SelectContent className="max-h-56">
                                        {minuteOptions.map((minute) => (
                                            <SelectItem
                                                key={minute}
                                                value={String(minute)}
                                            >
                                                {String(minute).padStart(2, '0')}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="col-span-3 mt-2">
                            <Select
                                value={
                                    date
                                        ? resolveMeridiem(date.getHours())
                                        : ''
                                }
                                onValueChange={setMeridiem}
                                disabled={!date}
                            >
                                <SelectTrigger className="w-full min-w-0">
                                    <SelectValue placeholder="AM/PM" />
                                </SelectTrigger>
                                <SelectContent className="max-h-56">
                                    <SelectItem value="AM">AM</SelectItem>
                                    <SelectItem value="PM">PM</SelectItem>
                                </SelectContent>
                            </Select>
                            </div>
                        </div>
                        <div className="mt-2">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="w-full"
                                onClick={() => setDate(undefined)}
                            >
                                Clear
                            </Button>
                        </div>
                    </div>
                </div>
            </PopoverContent>
        </Popover>
    );
}
