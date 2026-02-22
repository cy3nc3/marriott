import { Line, LineChart, XAxis, YAxis } from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';

interface TrendChartProps {
    data: {
        year: string;
        enrollees: number | null;
        isProjected: boolean;
    }[];
}

export function TrendChart({ data }: TrendChartProps) {
    const chartConfig = {
        actual: {
            label: 'Actual',
            color: 'var(--chart-1)',
        },
        projection: {
            label: 'Projected',
            color: 'var(--chart-2)',
        },
    } satisfies ChartConfig;

    const processedData = data.map((item, index) => {
        const isLastActual =
            !item.isProjected && (data[index + 1]?.isProjected ?? false);

        return {
            ...item,
            actual: item.isProjected ? null : item.enrollees,
            projection:
                item.isProjected || isLastActual ? item.enrollees : null,
        };
    });

    return (
        <Card className="col-span-1 md:col-span-2">
            <CardHeader>
                <CardTitle>Enrollment Forecast (Yearly)</CardTitle>
            </CardHeader>
            <CardContent className="pl-2">
                <div className="h-[350px] w-full">
                    <ChartContainer
                        config={chartConfig}
                        className="!aspect-auto h-full w-full !justify-start"
                    >
                        <LineChart
                            accessibilityLayer
                            data={processedData}
                            margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                        >
                            <XAxis
                                dataKey="year"
                                stroke="#888888"
                                fontSize={12}
                                tickLine={false}
                                axisLine={false}
                            />
                            <YAxis
                                width={36}
                                stroke="#888888"
                                fontSize={12}
                                tickLine={false}
                                axisLine={false}
                                tickMargin={6}
                                tickFormatter={(value) => `${value}`}
                            />
                            <ChartTooltip
                                cursor={{
                                    stroke: 'hsl(var(--muted))',
                                    strokeWidth: 2,
                                }}
                                content={
                                    <ChartTooltipContent
                                        indicator="dot"
                                        labelFormatter={(label, payload) => {
                                            const payloadRow = payload?.[0]
                                                ?.payload as
                                                | { isProjected?: boolean }
                                                | undefined;
                                            const displayLabel =
                                                typeof label === 'string' ||
                                                typeof label === 'number'
                                                    ? String(label)
                                                    : '';

                                            if (payloadRow?.isProjected) {
                                                return `${displayLabel} (Projected)`;
                                            }

                                            return displayLabel;
                                        }}
                                    />
                                }
                            />
                            <Line
                                type="monotone"
                                dataKey="actual"
                                stroke="var(--color-actual)"
                                strokeWidth={3}
                                dot={{
                                    r: 4,
                                    fill: 'var(--color-actual)',
                                    strokeWidth: 0,
                                }}
                                isAnimationActive={true}
                                connectNulls={false}
                            />
                            <Line
                                type="monotone"
                                dataKey="projection"
                                stroke="var(--color-projection)"
                                strokeWidth={3}
                                strokeDasharray="5 5"
                                dot={(props) => {
                                    const { cx, cy, payload } = props;

                                    if (payload?.isProjected) {
                                        return (
                                            <circle
                                                cx={cx}
                                                cy={cy}
                                                r={4}
                                                fill="var(--color-projection)"
                                                stroke="none"
                                            />
                                        );
                                    }

                                    return <g />;
                                }}
                                isAnimationActive={true}
                                connectNulls={false}
                            />
                        </LineChart>
                    </ChartContainer>
                </div>
            </CardContent>
        </Card>
    );
}
