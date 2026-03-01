import { format } from 'date-fns';
import { Calendar as CalendarIcon, ChevronLeft, ChevronRight } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type MonthPickerProps = {
    value?: string;
    onValueChange: (value: string) => void;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
};

const parseMonthValue = (value?: string): Date | undefined => {
    if (!value || !/^\d{4}-\d{2}$/.test(value)) {
        return undefined;
    }

    const [yearString, monthString] = value.split('-');
    const year = Number(yearString);
    const monthIndex = Number(monthString) - 1;

    if (!Number.isInteger(year) || !Number.isInteger(monthIndex) || monthIndex < 0 || monthIndex > 11) {
        return undefined;
    }

    return new Date(year, monthIndex, 1);
};

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const buildMonthValue = (year: number, monthIndex: number): string => {
    return `${year}-${String(monthIndex + 1).padStart(2, '0')}`;
};

export function MonthPicker({
    value,
    onValueChange,
    placeholder = 'Select month',
    className,
    disabled,
}: MonthPickerProps) {
    const selectedMonth = parseMonthValue(value);
    const [isOpen, setIsOpen] = useState(false);
    const [viewYear, setViewYear] = useState<number>(
        selectedMonth?.getFullYear() ?? new Date().getFullYear(),
    );

    useEffect(() => {
        if (selectedMonth) {
            setViewYear(selectedMonth.getFullYear());
        }
    }, [selectedMonth]);

    const selectMonth = (monthIndex: number) => {
        onValueChange(buildMonthValue(viewYear, monthIndex));
        setIsOpen(false);
    };

    return (
        <Popover open={isOpen} onOpenChange={setIsOpen}>
            <PopoverTrigger asChild>
                <Button
                    type="button"
                    variant="outline"
                    disabled={disabled}
                    className={cn(
                        'w-full justify-start text-left font-normal',
                        !selectedMonth && 'text-muted-foreground',
                        className,
                    )}
                >
                    <CalendarIcon className="mr-2 size-4" />
                    {selectedMonth ? format(selectedMonth, 'MMMM yyyy') : <span>{placeholder}</span>}
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-64 p-3" align="start">
                <div className="mb-3 flex items-center justify-between">
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={() => setViewYear((year) => year - 1)}
                    >
                        <ChevronLeft className="size-4" />
                    </Button>
                    <span className="text-sm font-medium">{viewYear}</span>
                    <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="size-8"
                        onClick={() => setViewYear((year) => year + 1)}
                    >
                        <ChevronRight className="size-4" />
                    </Button>
                </div>
                <div className="grid grid-cols-3 gap-2">
                    {monthLabels.map((monthLabel, monthIndex) => {
                        const isSelected =
                            selectedMonth &&
                            selectedMonth.getFullYear() === viewYear &&
                            selectedMonth.getMonth() === monthIndex;

                        return (
                            <Button
                                key={`${viewYear}-${monthLabel}`}
                                type="button"
                                size="sm"
                                variant={isSelected ? 'default' : 'outline'}
                                onClick={() => selectMonth(monthIndex)}
                            >
                                {monthLabel}
                            </Button>
                        );
                    })}
                </div>
            </PopoverContent>
        </Popover>
    );
}
