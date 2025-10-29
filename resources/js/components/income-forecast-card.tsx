import { IncomeForecast } from "@/types";
import { Badge } from "@/components/ui/badge";
import {
    TrendingUp,
    TrendingDown,
    Minus,
    DollarSign,
    ChevronDown,
    ChevronUp,
    Info,
} from "lucide-react";
import { ResponsiveContainer, BarChart, Bar, Cell, CartesianGrid, XAxis, YAxis } from 'recharts';
import { ChartTooltip } from '@/components/ui/chart';
import { useState } from "react";

interface IncomeForecastCardProps {
    forecast: IncomeForecast;
    cropName?: string;
}

const IncomeForecastCard = ({ forecast, cropName }: IncomeForecastCardProps) => {
    const [isExpanded, setIsExpanded] = useState(true);

    // Format currency (PHP)
    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 2,
        }).format(amount);
    };

    // Calculate the variance trend
    const varianceTrend = forecast.variance_from_expected_percent || 0;
    const isPositiveTrend = varianceTrend > 5;
    const isNegativeTrend = varianceTrend < -5;

    // Confidence badge color
    const getConfidenceBadgeColor = (confidence: string) => {
        switch (confidence) {
            case 'high':
                return 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300';
            case 'medium':
                return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300';
            case 'low':
                return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    // Price trend icon
    const getPriceTrendIcon = () => {
        const trend = forecast.price_component.trend;
        switch (trend) {
            case 'increasing':
                return <TrendingUp className="h-3 w-3 text-green-600 dark:text-green-400" />;
            case 'decreasing':
                return <TrendingDown className="h-3 w-3 text-red-600 dark:text-red-400" />;
            default:
                return <Minus className="h-3 w-3 text-gray-600 dark:text-gray-400" />;
        }
    };

    // Prepare data for income comparison bar chart
    const comparisonData = [
        {
            name: 'Pessimistic',
            value: forecast.pessimistic_income,
            color: '#ef4444', // red-500
        },
        {
            name: 'Predicted',
            value: forecast.predicted_income,
            color: '#059669', // emerald-600
        },
        {
            name: 'Optimistic',
            value: forecast.optimistic_income,
            color: '#10b981', // green-500
        },
    ];

    // Add expected if available
    if (forecast.expected_income) {
        comparisonData.push({
            name: 'Expected',
            value: forecast.expected_income,
            color: '#6b7280', // gray-500
        });
    }

    return (
        <div className="bg-card border border-border rounded-lg overflow-hidden">
            {/* Header */}
            <div
                className="p-4 cursor-pointer hover:bg-muted/50 transition-colors"
                onClick={() => setIsExpanded(!isExpanded)}
            >
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <DollarSign className="h-5 w-5 text-emerald-600 dark:text-emerald-400" />
                        <h3 className="text-sm font-semibold">Income Forecast</h3>
                        <Badge className={`text-xs px-1.5 py-0 ${getConfidenceBadgeColor(forecast.confidence)}`}>
                            {forecast.confidence} confidence
                        </Badge>
                    </div>
                    {isExpanded ? (
                        <ChevronUp className="h-4 w-4 text-muted-foreground" />
                    ) : (
                        <ChevronDown className="h-4 w-4 text-muted-foreground" />
                    )}
                </div>

                {/* Quick Summary (always visible) */}
                {!isExpanded && (
                    <div className="mt-2 flex items-center gap-4 text-xs">
                        <div>
                            <span className="text-muted-foreground">Predicted: </span>
                            <span className="font-semibold">{formatCurrency(forecast.predicted_income)}</span>
                        </div>
                        {forecast.expected_income && (
                            <div className="flex items-center gap-1">
                                {isPositiveTrend ? (
                                    <TrendingUp className="h-3 w-3 text-green-600" />
                                ) : isNegativeTrend ? (
                                    <TrendingDown className="h-3 w-3 text-red-600" />
                                ) : (
                                    <Minus className="h-3 w-3 text-gray-600" />
                                )}
                                <span className={isPositiveTrend ? 'text-green-600' : isNegativeTrend ? 'text-red-600' : 'text-gray-600'}>
                                    {varianceTrend > 0 ? '+' : ''}{varianceTrend.toFixed(1)}%
                                </span>
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Expanded Content */}
            {isExpanded && (
                <div className="px-4 pb-4 space-y-4">
                    {/* Main Metrics */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="p-3 bg-muted/30 rounded-lg">
                            <div className="text-xs text-muted-foreground mb-1">Predicted Income</div>
                            <div className="text-2xl font-bold text-emerald-600 dark:text-emerald-400">
                                {formatCurrency(forecast.predicted_income)}
                            </div>
                            <div className="text-xs mt-1 text-muted-foreground">
                                {formatCurrency(forecast.income_per_hectare)}/ha
                            </div>
                        </div>

                        {forecast.expected_income ? (
                            <div className="p-3 bg-muted/30 rounded-lg">
                                <div className="text-xs text-muted-foreground mb-1">Expected Income</div>
                                <div className="text-2xl font-bold">
                                    {formatCurrency(forecast.expected_income)}
                                </div>
                                <div className="flex items-center gap-1 mt-1">
                                    {isPositiveTrend ? (
                                        <TrendingUp className="h-3 w-3 text-green-600" />
                                    ) : isNegativeTrend ? (
                                        <TrendingDown className="h-3 w-3 text-red-600" />
                                    ) : (
                                        <Minus className="h-3 w-3 text-gray-600" />
                                    )}
                                    <span className={`text-xs font-medium ${isPositiveTrend ? 'text-green-600' : isNegativeTrend ? 'text-red-600' : 'text-gray-600'}`}>
                                        {varianceTrend > 0 ? '+' : ''}{varianceTrend.toFixed(1)}% variance
                                    </span>
                                </div>
                            </div>
                        ) : (
                            <div className="p-3 bg-muted/30 rounded-lg">
                                <div className="text-xs text-muted-foreground mb-1">Combined Confidence</div>
                                <div className="text-2xl font-bold">
                                    {forecast.confidence_score.toFixed(0)}%
                                </div>
                                <div className="text-xs text-muted-foreground">accuracy</div>
                                <div className="text-xs mt-1 text-muted-foreground">
                                    Yield + Price Analysis
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Income Range Comparison Chart */}
                    {comparisonData.length > 0 && (
                        <div className="space-y-2">
                            <div className="text-xs font-semibold">Income Forecast Range</div>
                            <div className="h-32">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={comparisonData} layout="vertical">
                                        <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                        <XAxis
                                            type="number"
                                            tick={{ fill: '#6b7280', fontSize: 11 }}
                                            stroke="#9ca3af"
                                        />
                                        <YAxis
                                            dataKey="name"
                                            type="category"
                                            width={80}
                                            tick={{ fill: '#6b7280', fontSize: 11 }}
                                            stroke="#9ca3af"
                                        />
                                        <ChartTooltip
                                            content={({ active, payload }) => {
                                                if (active && payload && payload.length) {
                                                    return (
                                                        <div className="bg-background border border-border rounded-lg p-2 shadow-lg">
                                                            <div className="text-xs font-semibold">{payload[0].payload.name}</div>
                                                            <div className="text-xs text-muted-foreground">
                                                                {formatCurrency(Number(payload[0].value))}
                                                            </div>
                                                        </div>
                                                    );
                                                }
                                                return null;
                                            }}
                                        />
                                        <Bar dataKey="value" radius={4}>
                                            {comparisonData.map((entry, index) => (
                                                <Cell key={`cell-${index}`} fill={entry.color} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </div>
                    )}

                    {/* Calculation Breakdown */}
                    <div className="space-y-2">
                        <div className="text-xs font-semibold">How it's calculated</div>
                        <div className="p-3 bg-muted/20 rounded-lg space-y-2">
                            <div className="grid grid-cols-3 gap-2 text-xs">
                                <div>
                                    <div className="text-muted-foreground">Yield Forecast</div>
                                    <div className="font-semibold">{forecast.calculation_breakdown.yield_kg.toFixed(2)} kg</div>
                                    <Badge className={`text-xs px-1 py-0 mt-1 ${getConfidenceBadgeColor(forecast.yield_component.confidence)}`}>
                                        {forecast.yield_component.confidence_score}% conf
                                    </Badge>
                                </div>
                                <div className="text-center self-center text-muted-foreground">×</div>
                                <div>
                                    <div className="text-muted-foreground">Price Forecast</div>
                                    <div className="font-semibold">{formatCurrency(forecast.calculation_breakdown.price_per_kg)}/kg</div>
                                    <div className="flex items-center gap-1 mt-1">
                                        {getPriceTrendIcon()}
                                        <span className="text-xs">{forecast.price_component.trend}</span>
                                    </div>
                                </div>
                            </div>
                            <div className="border-t border-border pt-2">
                                <div className="text-muted-foreground">Predicted Income</div>
                                <div className="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                                    {formatCurrency(forecast.calculation_breakdown.result)}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Component Breakdown */}
                    <div className="grid grid-cols-2 gap-3">
                        {/* Yield Component */}
                        <div className="p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded">
                            <div className="text-xs font-semibold text-purple-800 dark:text-purple-300 mb-2">
                                Yield Component
                            </div>
                            <div className="space-y-1 text-xs">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Range:</span>
                                    <span className="font-medium">
                                        {forecast.yield_component.pessimistic_yield.toFixed(0)} - {forecast.yield_component.optimistic_yield.toFixed(0)} kg
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Model:</span>
                                    <span className="font-medium">{forecast.yield_component.model_type === 'ml_regression' ? 'ML' : 'Basic'}</span>
                                </div>
                            </div>
                        </div>

                        {/* Price Component */}
                        <div className="p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded">
                            <div className="text-xs font-semibold text-blue-800 dark:text-blue-300 mb-2">
                                Price Component
                            </div>
                            <div className="space-y-1 text-xs">
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Current:</span>
                                    <span className="font-medium">{formatCurrency(forecast.price_component.current_price)}/kg</span>
                                </div>
                                <div className="flex justify-between">
                                    <span className="text-muted-foreground">Volatility:</span>
                                    <span className="font-medium">±{formatCurrency(forecast.price_component.price_volatility)}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Historical Income */}
                    {forecast.historical_income && forecast.historical_income.length > 0 && (
                        <div className="space-y-2">
                            <div className="text-xs font-semibold">
                                Historical Income
                                <span className="ml-2 text-muted-foreground font-normal">
                                    (Last {forecast.historical_income.length} harvests)
                                </span>
                            </div>
                            <div className="space-y-1.5 max-h-32 overflow-y-auto">
                                {forecast.historical_income.map((record, index) => (
                                    <div
                                        key={index}
                                        className="flex items-center justify-between p-2 bg-muted/20 rounded text-xs"
                                    >
                                        <div>
                                            <div className="font-medium">{new Date(record.harvest_date).toLocaleDateString('en-US', { month: 'short', year: 'numeric' })}</div>
                                            <div className="text-muted-foreground">{record.yield.toFixed(0)} kg from {record.hectares} ha</div>
                                        </div>
                                        <div className="text-right">
                                            <div className="font-semibold">{formatCurrency(record.income)}</div>
                                            <div className="text-muted-foreground">{formatCurrency(record.income_per_hectare)}/ha</div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Info Box */}
                    <div className="p-2 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded text-xs">
                        <div className="flex items-center gap-1 text-emerald-800 dark:text-emerald-300 font-medium">
                            <Info className="h-3 w-3" />
                            <span>Forecast Details</span>
                        </div>
                        <div className="text-emerald-700 dark:text-emerald-400 mt-1">
                            This income forecast combines AI-powered yield prediction with market price trend analysis.
                            Expected harvest in {forecast.days_until_harvest} days on {new Date(forecast.harvest_date).toLocaleDateString()}.
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default IncomeForecastCard;
