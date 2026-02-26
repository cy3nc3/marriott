import { CircleHelp } from 'lucide-react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { getInstallGuide } from '@/lib/pwa-install';

type PwaInstallGuideDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    message: string;
};

export function PwaInstallGuideDialog({
    open,
    onOpenChange,
    message,
}: PwaInstallGuideDialogProps) {
    const guide = getInstallGuide();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-[460px]">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <CircleHelp className="size-4" />
                        {guide.title}
                    </DialogTitle>
                    <DialogDescription>{message}</DialogDescription>
                </DialogHeader>
                <ol className="list-decimal space-y-2 pl-5 text-sm">
                    {guide.steps.map((step) => (
                        <li key={step}>{step}</li>
                    ))}
                </ol>
                {guide.note ? (
                    <p className="text-sm text-muted-foreground">
                        {guide.note}
                    </p>
                ) : null}
                <DialogFooter>
                    <Button type="button" onClick={() => onOpenChange(false)}>
                        Close
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
