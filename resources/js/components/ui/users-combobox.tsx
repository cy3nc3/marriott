import * as React from 'react';
import { Check, ChevronsUpDown, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import { cn } from '@/lib/utils';

type UserOption = {
    id: number;
    label: string;
    role?: string;
    role_label?: string;
};

interface UsersComboboxProps {
    options: UserOption[];
    selected: number[];
    onChange: (selected: number[]) => void;
    placeholder?: string;
    className?: string;
}

export function UsersCombobox({
    options,
    selected,
    onChange,
    placeholder = 'Select users...',
    className,
}: UsersComboboxProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');

    const selectedSet = React.useMemo(() => new Set(selected), [selected]);

    const filteredOptions = React.useMemo(() => {
        const normalizedSearch = search.trim().toLowerCase();

        if (normalizedSearch === '') {
            return options;
        }

        return options.filter((option) => {
            const roleText = option.role_label ?? option.role ?? '';

            return (
                option.label.toLowerCase().includes(normalizedSearch) ||
                roleText.toLowerCase().includes(normalizedSearch)
            );
        });
    }, [options, search]);

    const removeUser = (userId: number) => {
        onChange(selected.filter((id) => id !== userId));
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    className={cn(
                        'h-auto min-h-[40px] w-full justify-between p-2 hover:bg-background',
                        className,
                    )}
                >
                    <div className="flex flex-wrap gap-1">
                        {selected.length > 0 ? (
                            selected.map((userId) => {
                                const selectedUser = options.find(
                                    (option) => option.id === userId,
                                );

                                return (
                                    <Badge
                                        key={userId}
                                        variant="secondary"
                                        className="mb-1 mr-1"
                                        onClick={(event) => {
                                            event.stopPropagation();
                                            removeUser(userId);
                                        }}
                                    >
                                        {selectedUser?.label ?? `User #${userId}`}
                                        <button
                                            className="ml-1 rounded-full"
                                            onMouseDown={(event) => {
                                                event.preventDefault();
                                                event.stopPropagation();
                                            }}
                                            onClick={() => removeUser(userId)}
                                        >
                                            <X className="h-3 w-3" />
                                        </button>
                                    </Badge>
                                );
                            })
                        ) : (
                            <span className="text-sm text-muted-foreground">
                                {placeholder}
                            </span>
                        )}
                    </div>
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent
                className="w-[var(--radix-popover-trigger-width)] p-0"
                align="start"
            >
                <div className="border-b p-2">
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Search users..."
                        className="h-8"
                    />
                </div>

                <div className="max-h-64 overflow-y-auto p-1">
                    {filteredOptions.length === 0 ? (
                        <div className="py-6 text-center text-sm text-muted-foreground">
                            No users found.
                        </div>
                    ) : (
                        filteredOptions.map((option) => {
                            const isSelected = selectedSet.has(option.id);

                            return (
                                <button
                                    type="button"
                                    key={option.id}
                                    className={cn(
                                        'flex w-full items-center justify-between rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground',
                                        isSelected && 'bg-accent/50',
                                    )}
                                    onClick={() => {
                                        if (isSelected) {
                                            onChange(
                                                selected.filter(
                                                    (id) => id !== option.id,
                                                ),
                                            );
                                        } else {
                                            onChange([...selected, option.id]);
                                        }
                                    }}
                                >
                                    <div className="flex min-w-0 items-center gap-2">
                                        <div
                                            className={cn(
                                                'flex h-4 w-4 items-center justify-center rounded-sm border border-primary',
                                                isSelected
                                                    ? 'bg-primary text-primary-foreground'
                                                    : 'opacity-50 [&_svg]:invisible',
                                            )}
                                        >
                                            <Check className="h-3 w-3" />
                                        </div>
                                        <div className="min-w-0">
                                            <p className="truncate text-sm font-medium">
                                                {option.label}
                                            </p>
                                            {option.role_label && (
                                                <p className="truncate text-xs text-muted-foreground">
                                                    {option.role_label}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                </button>
                            );
                        })
                    )}
                </div>

                {selected.length > 0 && (
                    <div className="border-t p-1">
                        <Button
                            variant="ghost"
                            className="h-8 w-full"
                            onClick={() => onChange([])}
                        >
                            Clear All
                        </Button>
                    </div>
                )}
            </PopoverContent>
        </Popover>
    );
}
