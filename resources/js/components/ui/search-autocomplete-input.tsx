import * as React from 'react';
import { Search } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export interface SearchSuggestionOption {
    id: number | string;
    label: string;
    value?: string;
    description?: string;
    keywords?: string;
}

interface SearchAutocompleteInputProps
    extends Omit<
        React.ComponentProps<typeof Input>,
        'value' | 'onChange' | 'onSelect'
    > {
    value: string;
    onValueChange: (value: string) => void;
    suggestions?: SearchSuggestionOption[];
    onSelectSuggestion?: (option: SearchSuggestionOption) => void;
    onEnterPress?: () => void;
    maxSuggestions?: number;
    emptyMessage?: string;
    wrapperClassName?: string;
    showSuggestions?: boolean;
}

export function SearchAutocompleteInput({
    value,
    onValueChange,
    suggestions = [],
    onSelectSuggestion,
    onEnterPress,
    maxSuggestions = 5,
    emptyMessage = 'No matches found.',
    showSuggestions = true,
    className,
    wrapperClassName,
    onFocus,
    onBlur,
    onKeyDown,
    ...props
}: SearchAutocompleteInputProps) {
    const wrapperRef = React.useRef<HTMLDivElement | null>(null);
    const [isOpen, setIsOpen] = React.useState(false);
    const [activeIndex, setActiveIndex] = React.useState(-1);

    const filteredSuggestions = React.useMemo(() => {
        const query = value.trim().toLowerCase();

        if (!showSuggestions || query === '') {
            return [];
        }

        return suggestions
            .filter((option) => {
                const haystack = [
                    option.label,
                    option.value ?? '',
                    option.description ?? '',
                    option.keywords ?? '',
                ]
                    .join(' ')
                    .toLowerCase();

                return haystack.includes(query);
            })
            .slice(0, maxSuggestions);
    }, [maxSuggestions, showSuggestions, suggestions, value]);

    React.useEffect(() => {
        setActiveIndex(-1);
    }, [value]);

    React.useEffect(() => {
        const handlePointerDown = (event: MouseEvent) => {
            if (
                wrapperRef.current &&
                !wrapperRef.current.contains(event.target as Node)
            ) {
                setIsOpen(false);
            }
        };

        document.addEventListener('mousedown', handlePointerDown);

        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
        };
    }, []);

    const selectSuggestion = (option: SearchSuggestionOption) => {
        onValueChange(option.value ?? option.label);
        setIsOpen(false);
        setActiveIndex(-1);
        onSelectSuggestion?.(option);
    };

    const handleKeyDown: React.KeyboardEventHandler<HTMLInputElement> = (
        event,
    ) => {
        if (onKeyDown) {
            onKeyDown(event);
        }

        if (!showSuggestions || !isOpen || filteredSuggestions.length === 0) {
            if (event.key === 'Enter' && onEnterPress) {
                onEnterPress();
            }

            return;
        }

        if (event.key === 'ArrowDown') {
            event.preventDefault();
            setActiveIndex((currentIndex) =>
                currentIndex >= filteredSuggestions.length - 1
                    ? 0
                    : currentIndex + 1,
            );

            return;
        }

        if (event.key === 'ArrowUp') {
            event.preventDefault();
            setActiveIndex((currentIndex) =>
                currentIndex <= 0
                    ? filteredSuggestions.length - 1
                    : currentIndex - 1,
            );

            return;
        }

        if (event.key === 'Escape') {
            setIsOpen(false);
            setActiveIndex(-1);

            return;
        }

        if (event.key === 'Enter') {
            if (activeIndex >= 0 && activeIndex < filteredSuggestions.length) {
                event.preventDefault();
                selectSuggestion(filteredSuggestions[activeIndex]);

                return;
            }

            if (onEnterPress) {
                onEnterPress();
            }
        }
    };

    return (
        <div ref={wrapperRef} className={cn('relative', wrapperClassName)}>
            <Search className="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground" />
            <Input
                {...props}
                value={value}
                className={cn('pl-9', className)}
                onFocus={(event) => {
                    if (showSuggestions && value.trim() !== '') {
                        setIsOpen(true);
                    }

                    if (onFocus) {
                        onFocus(event);
                    }
                }}
                onBlur={(event) => {
                    if (onBlur) {
                        onBlur(event);
                    }
                }}
                onChange={(event) => {
                    const nextValue = event.target.value;
                    onValueChange(nextValue);
                    setIsOpen(showSuggestions && nextValue.trim() !== '');
                }}
                onKeyDown={handleKeyDown}
            />

            {showSuggestions && isOpen && (
                <div className="absolute top-full right-0 left-0 z-50 mt-1 overflow-hidden rounded-md border bg-popover shadow-md">
                    {filteredSuggestions.length === 0 ? (
                        <p className="px-3 py-2 text-sm text-muted-foreground">
                            {emptyMessage}
                        </p>
                    ) : (
                        <div className="max-h-60 overflow-y-auto p-1">
                            {filteredSuggestions.map((option, index) => (
                                <button
                                    key={option.id}
                                    type="button"
                                    className={cn(
                                        'flex w-full flex-col items-start rounded-sm px-2 py-1.5 text-left text-sm hover:bg-accent hover:text-accent-foreground',
                                        index === activeIndex && 'bg-accent',
                                    )}
                                    onMouseDown={(event) =>
                                        event.preventDefault()
                                    }
                                    onClick={() => selectSuggestion(option)}
                                >
                                    <span className="truncate font-medium">
                                        {option.label}
                                    </span>
                                    {option.description && (
                                        <span className="truncate text-xs text-muted-foreground">
                                            {option.description}
                                        </span>
                                    )}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}
