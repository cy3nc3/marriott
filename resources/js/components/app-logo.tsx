type AppLogoProps = {
    title?: string;
};

export default function AppLogo({ title = 'Marriott' }: AppLogoProps) {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-full bg-primary text-sm font-black text-primary-foreground">
                M
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    {title}
                </span>
            </div>
        </>
    );
}
