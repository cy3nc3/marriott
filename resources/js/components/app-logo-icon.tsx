import type { ComponentPropsWithoutRef } from 'react';
import { cn } from '@/lib/utils';

type AppLogoIconProps = ComponentPropsWithoutRef<'img'>;

export default function AppLogoIcon({
    className,
    alt = 'MarriottConnect logo',
    ...props
}: AppLogoIconProps) {
    return (
        <img
            src="/images/branding/marriott-school-seal.svg"
            alt={alt}
            className={cn('object-contain', className)}
            {...props}
        />
    );
}
