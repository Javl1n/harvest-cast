import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { TrendingUp, TrendingDown, Minus, AlertCircle, DollarSign, Activity } from 'lucide-react';
import { ChartContainer, ChartTooltip, ChartTooltipContent, ChartLegend, ChartLegendContent } from '@/components/ui/chart';
import { LineChart, Line, XAxis, YAxis, CartesianGrid } from 'recharts';
import { useSetPanelSize } from '@/hooks/use-set-panel-size';

interface Price {
    id: number;
    price: number;
    date: string;
}

interface CommodityVariant {
    id: number;
    name: string;
}

interface Commodity {
    id: number;
    name: string;
}

interface Forecast {
    trend: 'increasing' | 'decreasing' | 'stable';
    slope: number;
    price_change_percent: number;
    forecasts: Array<{
        date: string;
        price: number;
        days_ahead: number;
    }>;
    confidence: 'high' | 'medium' | 'low';
}

interface VariantData {
    variant: CommodityVariant;
    current_price: Price | null;
    price_history: Price[];
    forecast: Forecast | null;
}

interface CommodityData {
    commodity: Commodity;
    variants: VariantData[];
}

interface PageProps extends InertiaPageProps {
    forecastData: CommodityData[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pricing Forecast',
        href: '/pricing-forecast',
    },
];

// Chart colors for consistent theming
const CHART_COLORS = [
    '#22c55e', // green-500
    '#16a34a', // green-600  
    '#15803d', // green-700
    '#166534', // green-800
    '#14532d', // green-900
];

const PricingForecastIndex = () => {
    const { forecastData } = usePage<PageProps>().props;

    useSetPanelSize(56);

    const getTrendIcon = (trend: string) => {
        switch (trend) {
            case 'increasing':
                return <TrendingUp className="h-4 w-4 text-green-600" />;
            case 'decreasing':
                return <TrendingDown className="h-4 w-4 text-red-600" />;
            default:
                return <Minus className="h-4 w-4 text-gray-600" />;
        }
    };

    const getConfidenceColor = (confidence: string) => {
        switch (confidence) {
            case 'high':
                return 'text-green-600 bg-green-50';
            case 'medium':
                return 'text-yellow-600 bg-yellow-50';
            case 'low':
                return 'text-red-600 bg-red-50';
            default:
                return 'text-gray-600 bg-gray-50';
        }
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
        }).format(amount);
    };

    const formatDate = (dateString: string) => {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
        }).format(new Date(dateString));
    };

    // Prepare chart data for each commodity
    const prepareChartData = (commodityData: CommodityData) => {
        const chartData: { [key: string]: unknown }[] = [];
        const dateSet = new Set<string>();

        // Collect all unique dates from all variants
        commodityData.variants.forEach(variant => {
            variant.price_history.forEach(price => {
                dateSet.add(price.date);
            });
        });

        // Sort dates chronologically
        const sortedDates = Array.from(dateSet).sort();

        // Create data points for each date
        sortedDates.forEach(date => {
            const dataPoint: { [key: string]: unknown } = { date: formatDate(date) };
            
            commodityData.variants.forEach(variant => {
                const priceForDate = variant.price_history.find(p => p.date === date);
                if (priceForDate) {
                    dataPoint[variant.variant.name] = priceForDate.price;
                }
            });
            
            chartData.push(dataPoint);
        });

        return chartData;
    };

    // Prepare chart config using the theme colors
    const prepareChartConfig = (commodityData: CommodityData) => {
        const config: { [key: string]: { label: string; color: string } } = {};
        
        // Use shared chart colors
        
        commodityData.variants.forEach((variant, index) => {
            const colorIndex = index % CHART_COLORS.length;
            config[variant.variant.name] = {
                label: variant.variant.name,
                color: CHART_COLORS[colorIndex],
            };
        });
        
        return config;
    };

    const getVariantColor = (index: number) => {
        const colorIndex = index % CHART_COLORS.length;
        return CHART_COLORS[colorIndex];
    };

    return (
        <>
            <Head title="Pricing Forecast" />
            <div className="px-4 py-6">
                <div className="flex gap-4 items-center mb-6">
                    <Activity className="h-8 w-8 text-primary" />
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold">Pricing Forecast</h1>
                        <p className="text-sm text-muted-foreground">
                            Price forecasts and trends for commodities and variants
                        </p>
                    </div>
                </div>

                {forecastData.length === 0 ? (
                    <div className="text-center py-12">
                        <AlertCircle className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <h3 className="text-lg font-medium text-gray-900 mb-2">
                            No pricing data available
                        </h3>
                        <p className="text-gray-500">
                            Add commodities, variants, and price data to see forecasts.
                        </p>
                    </div>
                ) : (
                    <div className="space-y-8">
                        {forecastData.map((commodityData) => {
                            const chartData = prepareChartData(commodityData);
                            const chartConfig = prepareChartConfig(commodityData);
                            const hasData = chartData.length > 0 && commodityData.variants.length > 0;

                            return (
                                <div
                                    key={commodityData.commodity.id}
                                    className="bg-card rounded-lg border p-6"
                                >
                                    <div className="flex items-center gap-3 mb-6">
                                        <DollarSign className="h-6 w-6 text-primary" />
                                        <h2 className="text-xl font-semibold">
                                            {commodityData.commodity.name}
                                        </h2>
                                    </div>

                                    {!hasData ? (
                                        <p className="text-muted-foreground italic">
                                            No price data available for this commodity.
                                        </p>
                                    ) : (
                                        <>
                                            {/* Price History Chart */}
                                            <div className="mb-8">
                                                <h3 className="text-lg font-medium mb-4">Price History</h3>
                                                <ChartContainer
                                                    config={chartConfig}
                                                    className="h-[400px]"
                                                >
                                                    <LineChart
                                                        accessibilityLayer
                                                        data={chartData}
                                                        margin={{
                                                            left: 12,
                                                            right: 12,
                                                            top: 12,
                                                            bottom: 12,
                                                        }}
                                                    >
                                                        <CartesianGrid vertical={false} />
                                                        <XAxis
                                                            dataKey="date"
                                                            tickLine={false}
                                                            axisLine={false}
                                                            tickMargin={8}
                                                        />
                                                        <YAxis
                                                            tickLine={false}
                                                            axisLine={false}
                                                            tickMargin={8}
                                                            tickFormatter={(value) => `₱${value}`}
                                                        />
                                                        <ChartTooltip
                                                            cursor={false}
                                                            content={
                                                                <ChartTooltipContent
                                                                    indicator="line"
                                                                    formatter={(value, name) => [
                                                                        formatCurrency(Number(value)),
                                                                        name,
                                                                    ]}
                                                                />
                                                            }
                                                        />
                                                        <ChartLegend content={<ChartLegendContent />} />
                                        {commodityData.variants.map((variant, index) => {
                                                            const colorIndex = index % 5;
                                                            const color = CHART_COLORS[colorIndex];
                                                            
                                                            return (
                                                                <Line
                                                                    key={variant.variant.id}
                                                                    dataKey={variant.variant.name}
                                                                    type="monotone"
                                                                    stroke={color}
                                                                    strokeWidth={2}
                                                                    dot={{
                                                                        fill: color,
                                                                        strokeWidth: 2,
                                                                        r: 4,
                                                                    }}
                                                                    activeDot={{
                                                                        r: 6,
                                                                    }}
                                                                    connectNulls={false}
                                                                />
                                                            );
                                                        })}
                                                    </LineChart>
                                                </ChartContainer>
                                            </div>

                                            {/* Variants Summary */}
                                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                                {commodityData.variants.map((variantData, index) => (
                                                    <div
                                                        key={variantData.variant.id}
                                                        className="bg-background rounded-lg border p-4"
                                                        style={{
                                                            borderLeft: `4px solid ${getVariantColor(index)}`,
                                                        }}
                                                    >
                                                        <div className="flex items-start justify-between mb-3">
                                                            <h3 className="font-medium">
                                                                {variantData.variant.name}
                                                            </h3>
                                                            {variantData.forecast && (
                                                                <div className="flex items-center gap-1">
                                                                    {getTrendIcon(variantData.forecast.trend)}
                                                                    <span
                                                                        className={`text-xs px-2 py-1 rounded ${getConfidenceColor(
                                                                            variantData.forecast.confidence
                                                                        )}`}
                                                                    >
                                                                        {variantData.forecast.confidence}
                                                                    </span>
                                                                </div>
                                                            )}
                                                        </div>

                                                        <div className="space-y-3">
                                                            {variantData.current_price ? (
                                                                <div className="flex items-center justify-between">
                                                                    <span className="text-sm text-muted-foreground">
                                                                        Current Price
                                                                    </span>
                                                                    <div className="text-right">
                                                                        <div className="font-semibold">
                                                                            {formatCurrency(variantData.current_price.price)}
                                                                        </div>
                                                                        <div className="text-xs text-muted-foreground">
                                                                            {formatDate(variantData.current_price.date)}
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            ) : (
                                                                <div className="text-sm text-muted-foreground italic">
                                                                    No current price data
                                                                </div>
                                                            )}

                                                            {variantData.forecast && (
                                                                <div className="flex items-center justify-between">
                                                                    <span className="text-sm text-muted-foreground">
                                                                        Price Change
                                                                    </span>
                                                                    <span
                                                                        className={`text-sm font-medium ${
                                                                            variantData.forecast.price_change_percent > 0
                                                                                ? 'text-green-600'
                                                                                : variantData.forecast.price_change_percent < 0
                                                                                ? 'text-red-600'
                                                                                : 'text-gray-600'
                                                                        }`}
                                                                    >
                                                                        {variantData.forecast.price_change_percent > 0 ? '+' : ''}
                                                                        {variantData.forecast.price_change_percent.toFixed(1)}%
                                                                    </span>
                                                                </div>
                                                            )}

                                                            {variantData.forecast && (
                                                                <div className="border-t pt-3">
                                                                    <div className="text-xs font-medium text-muted-foreground mb-2">
                                                                        FORECASTS
                                                                    </div>
                                                                    <div className="space-y-1">
                                                                        {variantData.forecast.forecasts.map((forecast, index) => (
                                                                            <div
                                                                                key={index}
                                                                                className="flex justify-between text-xs"
                                                                            >
                                                                                <span className="text-muted-foreground">
                                                                                    {forecast.days_ahead}d
                                                                                </span>
                                                                                <span className="font-medium">
                                                                                    {formatCurrency(forecast.price)}
                                                                                </span>
                                                                            </div>
                                                                        ))}
                                                                    </div>
                                                                </div>
                                                            )}

                                                            {!variantData.forecast && variantData.price_history.length > 0 && (
                                                                <div className="text-xs text-amber-600 bg-amber-50 p-2 rounded">
                                                                    Insufficient data for forecasting (need at least 3 price points)
                                                                </div>
                                                            )}

                                                            {variantData.price_history.length === 0 && (
                                                                <div className="text-xs text-gray-500 bg-gray-50 p-2 rounded">
                                                                    No price history available
                                                                </div>
                                                            )}
                                                        </div>
                                                    </div>
                                                ))}
                                            </div>
                                        </>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </>
    );
};

PricingForecastIndex.layout = (page: React.ReactNode) => <AppLayout children={page} breadcrumbs={breadcrumbs} />;

export default PricingForecastIndex;