import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetFooter,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import { cn } from '@/lib/utils';
import type { SharedData } from '@/types';

type ResponsiveFormDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
    contentClassName?: string;
    bodyClassName?: string;
};

export function ResponsiveFormDialog({
    open,
    onOpenChange,
    title,
    description,
    children,
    footer,
    contentClassName,
    bodyClassName,
}: ResponsiveFormDialogProps) {
    const { ui } = usePage<SharedData>().props;
    const isHandheld = Boolean(ui?.is_handheld);

    if (isHandheld) {
        return (
            <Sheet open={open} onOpenChange={onOpenChange}>
                <SheetContent
                    side="bottom"
                    className={cn(
                        'h-dvh w-full max-w-none rounded-none p-0',
                        contentClassName,
                    )}
                >
                    <SheetHeader className="border-b px-4 py-3 text-left">
                        <SheetTitle>{title}</SheetTitle>
                        {description ? (
                            <SheetDescription>{description}</SheetDescription>
                        ) : null}
                    </SheetHeader>
                    <div
                        className={cn(
                            'flex-1 overflow-y-auto px-4 py-4',
                            bodyClassName,
                        )}
                    >
                        {children}
                    </div>
                    {footer ? (
                        <SheetFooter className="border-t bg-background px-4 py-3">
                            {footer}
                        </SheetFooter>
                    ) : null}
                </SheetContent>
            </Sheet>
        );
    }

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className={contentClassName}>
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    {description ? (
                        <DialogDescription>{description}</DialogDescription>
                    ) : null}
                </DialogHeader>
                <div className={bodyClassName}>{children}</div>
                {footer}
            </DialogContent>
        </Dialog>
    );
}
