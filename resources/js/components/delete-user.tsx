import { useForm } from '@inertiajs/react';
import { useRef, useState } from 'react';
import { ActionConfirmDialog } from '@/components/action-confirm-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { PasswordInput } from '@/components/ui/password-input';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';

export default function DeleteUser() {
    const passwordInput = useRef<HTMLInputElement>(null);
    const [isConfirmOpen, setIsConfirmOpen] = useState(false);

    const {
        data,
        setData,
        delete: destroy,
        processing,
        errors,
        reset,
        clearErrors,
    } = useForm({
        password: '',
    });

    const submitDelete = () => {
        destroy(ProfileController.destroy().url, {
            preserveScroll: true,
            onFinish: () => reset(),
            onError: () => passwordInput.current?.focus(),
            onSuccess: () => setIsConfirmOpen(false),
        });
    };

    const handleCancel = () => {
        setIsConfirmOpen(false);
        reset();
        clearErrors();
    };

    return (
        <div className="space-y-6">
            <Heading
                variant="small"
                title="Delete account"
                description="Delete your account and all of its resources"
            />
            <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                    <p className="font-medium text-destructive">Warning</p>
                    <p className="text-sm">
                        Please proceed with caution, this cannot be undone.
                    </p>
                </div>

                <Button
                    variant="destructive"
                    data-test="delete-user-button"
                    onClick={() => setIsConfirmOpen(true)}
                >
                    Delete account
                </Button>

                <ActionConfirmDialog
                    open={isConfirmOpen}
                    onOpenChange={setIsConfirmOpen}
                    onCancel={handleCancel}
                    title="Delete your account?"
                    description="Once your account is deleted, all of its resources and data will also be permanently deleted. Please enter your password to confirm."
                    variant="destructive"
                    confirmLabel="Delete Account"
                    onConfirm={submitDelete}
                    loading={processing}
                >
                    <div className="grid gap-2 overflow-hidden pb-1">
                        <Label htmlFor="password" title="Password" className="sr-only">
                            Password
                        </Label>

                        <PasswordInput
                            id="password"
                            name="password"
                            ref={passwordInput}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder="Enter your account password"
                            autoComplete="current-password"
                            className="h-11 shadow-sm"
                        />

                        <InputError message={errors.password} />
                    </div>
                </ActionConfirmDialog>
            </div>
        </div>
    );
}
