import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import { AlertTriangle, Info, Loader2 } from 'lucide-react';
import React from 'react';

interface ActionConfirmDialogProps {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: string;
    onConfirm?: () => void;
    onCancel?: () => void;
    confirmLabel?: string;
    cancelLabel?: string;
    loading?: boolean;
    variant?: 'default' | 'destructive' | 'warning';
    children?: React.ReactNode;
    confirmButtonType?: 'button' | 'submit';
}

export function ActionConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    onConfirm,
    onCancel,
    confirmLabel = 'Confirm',
    cancelLabel = 'Cancel',
    loading = false,
    variant = 'default',
    children,
    confirmButtonType = 'button',
}: ActionConfirmDialogProps) {
    const handleConfirm = () => {
        if (onConfirm) onConfirm();
        if (confirmButtonType === 'button') {
            onOpenChange(false);
        }
    };
    const handleCancel = () => {
        if (onCancel) onCancel();
        onOpenChange(false);
    };

    const variantStyles = {
        default: {
            icon: <Info className="h-5 w-5 text-blue-600 dark:text-blue-400" />,
            iconBg: 'bg-blue-500/15 border-blue-200 dark:border-blue-800/50',
            buttonVariant: 'default' as const,
        },
        destructive: {
            icon: <AlertTriangle className="h-5 w-5 text-red-600 dark:text-red-400" />,
            iconBg: 'bg-red-500/15 border-red-200 dark:border-red-800/50',
            buttonVariant: 'destructive' as const,
        },
        warning: {
            icon: <AlertTriangle className="h-5 w-5 text-amber-600 dark:text-amber-400" />,
            iconBg: 'bg-amber-500/15 border-amber-200 dark:border-amber-800/50',
            buttonVariant: 'default' as const, // We might want a custom-styled button for warning if needed
        },
    };

    const currentStyle = variantStyles[variant];

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[440px] gap-0 p-0 overflow-hidden border-none shadow-2xl">
                <div className="p-6">
                    <DialogHeader className="space-y-4">
                        <div className="flex items-center gap-4">
                            <div className={cn(
                                "flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border transition-colors",
                                currentStyle.iconBg
                            )}>
                                {currentStyle.icon}
                            </div>
                            <DialogTitle className="text-xl font-bold tracking-tight">
                                {title}
                            </DialogTitle>
                        </div>
                        <DialogDescription className="text-base text-muted-foreground leading-relaxed pt-1">
                            {description}
                        </DialogDescription>
                    </DialogHeader>

                    {children && (
                        <div className="mt-4 px-1">
                            {children}
                        </div>
                    )}
                </div>

                <DialogFooter className="bg-muted/30 dark:bg-muted/10 border-t p-4 px-6 flex-row gap-3 sm:justify-end">
                    <Button
                        variant="ghost"
                        onClick={handleCancel}
                        disabled={loading}
                        className="flex-1 sm:flex-none font-medium hover:bg-background/80"
                    >
                        {cancelLabel}
                    </Button>
                    <Button
                        variant={currentStyle.buttonVariant}
                        type={confirmButtonType}
                        onClick={handleConfirm}
                        disabled={loading}
                        className={cn(
                            "flex-1 sm:flex-none font-semibold min-w-[100px] shadow-sm",
                            variant === 'warning' && "bg-amber-600 hover:bg-amber-700 text-white border-amber-700"
                        )}
                    >
                        {loading && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                        {loading ? 'Processing...' : confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
