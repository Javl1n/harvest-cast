import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage, Link, router } from '@inertiajs/react';
import { PageProps as InertiaPageProps } from '@inertiajs/core';
import { TrendingUp, TrendingDown, Minus, DollarSign, Activity, ArrowLeft } from 'lucide-react';
import { ChartContainer, ChartTooltip, ChartTooltipContent, ChartLegend, ChartLegendContent } from '@/components/ui/chart';
import { LineChart, Line, XAxis, YAxis, CartesianGrid } from 'recharts';
import { useSetPanelSize } from '@/hooks/use-set-panel-size';
import { Skeleton } from '@/components/ui/skeleton';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import pricingForecast from '@/routes/pricing-forecast';

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
        days_ahead?: number;
        months_ahead?: number;
        type: 'daily' | 'monthly';
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
    forecastData?: CommodityData | null;
    selectedPeriod: string;
}

// Chart colors for consistent theming
const CHART_COLORS = [
    '#22c55e', // green-500
    '#16a34a', // green-600
    '#15803d', // green-700
    '#166534', // green-800
    '#14532d', // green-900
];

// Loading skeleton component
const LoadingSkeleton = () => (
    <div className="space-y-6 sm:space-y-8">
        <div className="bg-card rounded-lg border p-4 sm:p-6">
            <div className="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                <Skeleton className="h-5 w-5 sm:h-6 sm:w-6 rounded" />
                <Skeleton className="h-5 sm:h-6 w-32 sm:w-48" />
            </div>

            {/* Chart skeleton */}
            <div className="mb-6 sm:mb-8 -mx-4 sm:mx-0">
                <Skeleton className="h-4 sm:h-5 w-24 sm:w-32 mb-3 sm:mb-4 mx-4 sm:mx-0" />
                <div className="overflow-x-auto">
                    <Skeleton className="h-[250px] sm:h-[350px] lg:h-[400px] w-full rounded-lg" />
                </div>
            </div>

            {/* Variant cards skeleton */}
            <div className="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                {[1, 2, 3].map((variantIndex) => (
                    <div key={variantIndex} className="bg-background rounded-lg border p-3 sm:p-4 space-y-2 sm:space-y-3">
                        <Skeleton className="h-4 sm:h-5 w-28 sm:w-32" />
                        <Skeleton className="h-3 sm:h-4 w-full" />
                        <Skeleton className="h-3 sm:h-4 w-3/4" />
                        <div className="space-y-1.5 sm:space-y-2 pt-2 sm:pt-3">
                            <Skeleton className="h-3 w-16 sm:w-20" />
                            <Skeleton className="h-3 w-full" />
                            <Skeleton className="h-3 w-full" />
                            <Skeleton className="h-3 w-full" />
                        </div>
                    </div>
                ))}
            </div>
        </div>
    </div>
);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pricing Forecast',
        href: pricingForecast.index().url,
    },
];

const PricingForecastShow = () => {
    const { forecastData, selectedPeriod } = usePage<PageProps>().props;

    useSetPanelSize(56);

    // Show loading skeleton if data is not loaded yet
    const isLoading = !forecastData;

    const handlePeriodChange = (period: string) => {
        router.get(
            window.location.pathname,
            { period },
            { preserveState: true, preserveScroll: true }
        );
    };

    const getPeriodLabel = (period: string) => {
        switch (period) {
            case '30days':
                return 'Last 30 Days';
            case '90days':
                return 'Last 90 Days';
            case '6months':
                return 'Last 6 Months';
            case 'year':
                return 'Current Year';
            case 'all':
                return 'All Time';
            default:
                return 'Last 90 Days';
        }
    };

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
            year: 'numeric',
        }).format(new Date(dateString));
    };

    const formatTooltipDate = (dateString: string) => {
        return new Intl.DateTimeFormat('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric',
        }).format(new Date(dateString));
    };

    // Prepare chart data for the commodity
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

        // Group dates by month for cleaner x-axis display
        const monthMap = new Map<string, string[]>();
        sortedDates.forEach(date => {
            const monthKey = formatDate(date); // "Mar 2025" format
            if (!monthMap.has(monthKey)) {
                monthMap.set(monthKey, []);
            }
            monthMap.get(monthKey)!.push(date);
        });

        // Create data points for each date but with month grouping awareness
        sortedDates.forEach((date, index) => {
            const monthKey = formatDate(date);
            const isFirstInMonth = monthMap.get(monthKey)![0] === date;

            const dataPoint: { [key: string]: unknown } = {
                date: monthKey, // This will show month-year format
                fullDate: date, // Keep the full date for internal reference
                isFirstInMonth: isFirstInMonth,
                index: index
            };

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
            <Head title={forecastData ? `${forecastData.commodity.name} - Pricing Forecast` : 'Pricing Forecast'} />
            <div className="px-4 sm:px-6 py-4 sm:py-6">
                <div className="flex gap-3 sm:gap-4 items-center mb-4 sm:mb-6">
                    <Link href={pricingForecast.index()}>
                        <Button variant="ghost" size="sm" className="p-1">
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                    </Link>
                    <Activity className="h-6 w-6 sm:h-8 sm:w-8 text-primary flex-shrink-0" />
                    <div className="flex-1 min-w-0">
                        <h1 className="text-xl sm:text-2xl lg:text-3xl font-bold truncate">
                            {forecastData?.commodity.name || 'Loading...'}
                        </h1>
                        <p className="text-xs sm:text-sm text-muted-foreground mt-0.5 sm:mt-1">
                            Detailed price history and forecasts for all variants
                        </p>
                    </div>
                    <Select value={selectedPeriod} onValueChange={handlePeriodChange}>
                        <SelectTrigger className="w-[140px] sm:w-[180px]">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="30days">Last 30 Days</SelectItem>
                            <SelectItem value="90days">Last 90 Days</SelectItem>
                            <SelectItem value="6months">Last 6 Months</SelectItem>
                            <SelectItem value="year">Current Year</SelectItem>
                            <SelectItem value="all">All Time</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {isLoading ? (
                    <LoadingSkeleton />
                ) : forecastData && (
                    <div className="space-y-6 sm:space-y-8">
                        <div className="bg-card rounded-lg border p-4 sm:p-6">
                            <div className="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                                <DollarSign className="h-5 w-5 sm:h-6 sm:w-6 text-primary flex-shrink-0" />
                                <h2 className="text-lg sm:text-xl font-semibold truncate">
                                    {forecastData.commodity.name}
                                </h2>
                            </div>

                            {forecastData.variants.length === 0 ? (
                                <p className="text-muted-foreground italic">
                                    No price data available for this commodity.
                                </p>
                            ) : (
                                <>
                                    {/* Price History Chart */}
                                    <div className="mb-6 sm:mb-8 -mx-4 sm:mx-0">
                                        <h3 className="text-base sm:text-lg font-medium mb-3 sm:mb-4 px-4 sm:px-0">
                                            Price History ({getPeriodLabel(selectedPeriod)})
                                        </h3>
                                        <div className="overflow-x-auto">
                                            <ChartContainer
                                                config={prepareChartConfig(forecastData)}
                                                className="h-[250px] sm:h-[350px] lg:h-[400px] w-full min-w-full"
                                            >
                                                <LineChart
                                                    accessibilityLayer
                                                    data={prepareChartData(forecastData)}
                                                    margin={{
                                                        left: 4,
                                                        right: 4,
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
                                                        interval="preserveStartEnd"
                                                        tick={{ fontSize: 10 }}
                                                        tickFormatter={(value, index) => {
                                                            // Show only unique month labels
                                                            const chartData = prepareChartData(forecastData);
                                                            const currentData = chartData[index];
                                                            const prevData = chartData[index - 1];
                                                            if (!prevData || prevData.date !== currentData?.date) {
                                                                return value;
                                                            }
                                                            return '';
                                                        }}
                                                    />
                                                    <YAxis
                                                        tickLine={false}
                                                        axisLine={false}
                                                        tickMargin={4}
                                                        width={35}
                                                        tick={{ fontSize: 10 }}
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
                                                                labelFormatter={(label, payload) => {
                                                                    if (payload && payload[0] && payload[0].payload) {
                                                                        const fullDate = payload[0].payload.fullDate;
                                                                        return fullDate ? formatTooltipDate(fullDate) : label;
                                                                    }
                                                                    return label;
                                                                }}
                                                            />
                                                        }
                                                    />
                                                    <ChartLegend content={<ChartLegendContent />} />
                                                    {forecastData.variants.map((variant, index) => {
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
                                                                    strokeWidth: 0,
                                                                    r: 0,
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
                                    </div>

                                    {/* Variants Summary */}
                                    <div className="grid gap-3 sm:gap-4 grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                                        {forecastData.variants.map((variantData, index) => (
                                            <div
                                                key={variantData.variant.id}
                                                className="bg-background rounded-lg border p-3 sm:p-4"
                                                style={{
                                                    borderLeft: `4px solid ${getVariantColor(index)}`,
                                                }}
                                            >
                                                <div className="flex items-start justify-between mb-2 sm:mb-3 gap-2">
                                                    <h3 className="font-medium text-sm sm:text-base truncate">
                                                        {variantData.variant.name}
                                                    </h3>
                                                    {variantData.forecast && (
                                                        <div className="flex items-center gap-1 flex-shrink-0">
                                                            {getTrendIcon(variantData.forecast.trend)}
                                                            <span
                                                                className={`text-xs px-1.5 sm:px-2 py-0.5 sm:py-1 rounded ${getConfidenceColor(
                                                                    variantData.forecast.confidence
                                                                )}`}
                                                            >
                                                                {variantData.forecast.confidence}
                                                            </span>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="space-y-2 sm:space-y-3">
                                                    {variantData.current_price ? (
                                                        <div className="flex items-center justify-between gap-2">
                                                            <span className="text-xs sm:text-sm text-muted-foreground">
                                                                Current Price
                                                            </span>
                                                            <div className="text-right">
                                                                <div className="font-semibold text-sm sm:text-base">
                                                                    {formatCurrency(variantData.current_price.price)}
                                                                </div>
                                                                <div className="text-xs text-muted-foreground">
                                                                    {formatDate(variantData.current_price.date)}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    ) : (
                                                        <div className="text-xs sm:text-sm text-muted-foreground italic">
                                                            No current price data
                                                        </div>
                                                    )}

                                                    {variantData.forecast && (
                                                        <div className="flex items-center justify-between gap-2">
                                                            <span className="text-xs sm:text-sm text-muted-foreground">
                                                                Price Change
                                                            </span>
                                                            <span
                                                                className={`text-xs sm:text-sm font-medium ${
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
                                                        <div className="border-t pt-2 sm:pt-3">
                                                            <div className="text-xs font-medium text-muted-foreground mb-1.5 sm:mb-2">
                                                                FORECASTS
                                                            </div>

                                                            {/* Daily forecasts */}
                                                            <div className="space-y-0.5 sm:space-y-1 mb-2 sm:mb-3">
                                                                <div className="text-xs text-muted-foreground font-medium">Daily</div>
                                                                {variantData.forecast.forecasts
                                                                    .filter(f => f.type === 'daily')
                                                                    .map((forecast, index) => (
                                                                    <div
                                                                        key={`daily-${index}`}
                                                                        className="flex justify-between text-xs pl-1.5 sm:pl-2 gap-2"
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

                                                            {/* Monthly forecasts */}
                                                            <div className="space-y-0.5 sm:space-y-1">
                                                                <div className="text-xs text-muted-foreground font-medium">Monthly</div>
                                                                {variantData.forecast.forecasts
                                                                    .filter(f => f.type === 'monthly')
                                                                    .map((forecast, index) => (
                                                                    <div
                                                                        key={`monthly-${index}`}
                                                                        className="flex justify-between text-xs pl-1.5 sm:pl-2 gap-2"
                                                                    >
                                                                        <span className="text-muted-foreground">
                                                                            {forecast.months_ahead}mo
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
                    </div>
                )}
            </div>
        </>
    );
};

PricingForecastShow.layout = (page: React.ReactNode) => <AppLayout children={page} breadcrumbs={breadcrumbs} />;

export default PricingForecastShow;
