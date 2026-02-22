import { Bar, BarChart, CartesianGrid, XAxis, YAxis } from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    ChartContainer,
    ChartTooltip,
    ChartTooltipContent,
    type ChartConfig,
} from '@/components/ui/chart';

interface EnrollmentChartProps {
    data: { name: string; count: number }[];
}

export function EnrollmentChart({ data }: EnrollmentChartProps) {
    const chartConfig = {
        count: {
            label: 'Enrollees',
            color: 'var(--chart-1)',
        },
    } satisfies ChartConfig;

    return (
        <Card className="col-span-1">
            <CardHeader>
                <CardTitle>Enrollment by Grade</CardTitle>
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
                            margin={{ top: 8, right: 8, bottom: 0, left: 0 }}
                        >
                            <CartesianGrid vertical={false} />
                            <XAxis
                                dataKey="name"
                                stroke="#888888"
                                fontSize={12}
                                tickLine={false}
                                axisLine={false}
                                tickMargin={8}
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
                                content={
                                    <ChartTooltipContent indicator="dot" />
                                }
                            />
                            <Bar
                                dataKey="count"
                                fill="var(--color-count)"
                                radius={[4, 4, 0, 0]}
                            />
                        </BarChart>
                    </ChartContainer>
                </div>
            </CardContent>
        </Card>
    );
}
