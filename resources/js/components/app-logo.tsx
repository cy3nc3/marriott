import AppLogoIcon from '@/components/app-logo-icon';

type AppLogoProps = {
    title?: string;
};

export default function AppLogo({ title = 'MarriottConnect' }: AppLogoProps) {
    return (
        <>
            <div className="flex size-9 items-center justify-center overflow-hidden rounded-full ring-1 ring-border/80">
                <AppLogoIcon className="size-9" />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate font-semibold tracking-tight">
                    {title}
                </span>
            </div>
        </>
    );
}
