import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';

interface WorkloadChartProps {
    data: { name: string; full_name: string; count: number }[];
}

export function WorkloadChart({ data }: WorkloadChartProps) {
    const chartConfig = {
        count: {
            label: 'Classes',
            color: 'var(--chart-1)',
        },
    } satisfies ChartConfig;

    return (
        <Card className="col-span-1">
            <CardHeader>
                <CardTitle>Teacher Workload (Top 10)</CardTitle>
            </CardHeader>
            <CardContent className="pl-2">
                <div className="h-[350px] w-full">
                    <ChartContainer
                        config={chartConfig}
                        className="!aspect-auto h-full w-full !justify-start"
                    >
                        <BarChart
                            accessibilityLayer
                            data={data}
                            layout="vertical"
                            margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                        >
                            <CartesianGrid horizontal={false} />
                            <XAxis type="number" hide />
                            <YAxis
                                dataKey="name"
                                type="category"
                                width={64}
                                stroke="#888888"
                                fontSize={12}
                                tickLine={false}
                                axisLine={false}
                                tickMargin={6}
                            />
                            <ChartTooltip
                                content={
                                    <ChartTooltipContent
                                        indicator="dot"
                                        labelFormatter={(_, payload) => {
                                            return (
                                                (
                                                    payload?.[0]?.payload as {
                                                        full_name?: string;
                                                    }
                                                )?.full_name ?? ''
                                            );
                                        }}
                                    />
                                }
                            />
                            <Bar
                                dataKey="count"
                                fill="var(--color-count)"
                                radius={[0, 4, 4, 0]}
                            />
                        </BarChart>
                    </ChartContainer>
                </div>
            </CardContent>
        </Card>
    );
}
