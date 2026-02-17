import {
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface TrendChartProps {
    data: {
        year: string;
        enrollees: number | null;
        isProjected: boolean;
    }[];
}

export function TrendChart({ data }: TrendChartProps) {
    // Process data to create two series that connect at the transition point
    const processedData = data.map((item, index) => {
        const isLastActual =
            !item.isProjected && (data[index + 1]?.isProjected ?? false);

        return {
            ...item,
            actual: item.isProjected ? null : item.enrollees,
            // Projection series includes the last actual point to ensure the line is connected
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
                    <ResponsiveContainer width="100%" height="100%">
                        <LineChart data={processedData}>
                            <XAxis
                                dataKey="year"
                                stroke="#888888"
                                fontSize={12}
                                tickLine={false}
                                axisLine={false}
                            />
                            <YAxis
                                stroke="#888888"
                                fontSize={12}
                                tickLine={false}
                                axisLine={false}
                                tickFormatter={(value) => `${value}`}
                            />
                            <Tooltip
                                cursor={{
                                    stroke: 'hsl(var(--muted))',
                                    strokeWidth: 2,
                                }}
                                content={({ active, payload }) => {
                                    if (active && payload && payload.length) {
                                        // Find the first payload that has a value (either actual or projection)
                                        const activePayload =
                                            payload.find(
                                                (p) => p.value !== null,
                                            ) || payload[0];
                                        const item = activePayload.payload;
                                        return (
                                            <div className="rounded-lg border bg-background p-2 shadow-sm">
                                                <div className="grid grid-cols-2 gap-2">
                                                    <div className="flex flex-col">
                                                        <span className="text-[0.70rem] text-muted-foreground uppercase">
                                                            Year
                                                        </span>
                                                        <span className="font-bold text-muted-foreground">
                                                            {item.year}
                                                        </span>
                                                    </div>
                                                    <div className="flex flex-col">
                                                        <span className="text-[0.70rem] text-muted-foreground uppercase">
                                                            Enrollees
                                                        </span>
                                                        <span className="font-bold">
                                                            {item.enrollees}
                                                            {item.isProjected && (
                                                                <span className="ml-1 text-[10px] text-amber-500">
                                                                    (Projected)
                                                                </span>
                                                            )}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        );
                                    }
                                    return null;
                                }}
                            />
                            {/* Actual Data Line */}
                            <Line
                                type="monotone"
                                dataKey="actual"
                                stroke="currentColor"
                                strokeWidth={3}
                                dot={{
                                    r: 4,
                                    fill: 'currentColor',
                                    className: 'fill-primary',
                                    strokeWidth: 0,
                                }}
                                className="stroke-primary"
                                isAnimationActive={true}
                                connectNulls={false}
                            />
                            {/* Projected Data Line */}
                            <Line
                                type="monotone"
                                dataKey="projection"
                                stroke="#f59e0b"
                                strokeWidth={3}
                                strokeDasharray="5 5"
                                dot={(props) => {
                                    const { cx, cy, payload } = props;
                                    // Only draw dot for the actual projected year, not the connection point
                                    if (payload.isProjected) {
                                        return (
                                            <circle
                                                cx={cx}
                                                cy={cy}
                                                r={4}
                                                fill="#f59e0b"
                                                stroke="none"
                                            />
                                        );
                                    }
                                    return null;
                                }}
                                isAnimationActive={true}
                                connectNulls={false}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </CardContent>
        </Card>
    );
}
