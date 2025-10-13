import { WeatherInterface } from '@/types';
import { Cloud, Droplets, Thermometer, Wind, Eye, Gauge, ArrowUp } from 'lucide-react';
import { useState } from 'react';

interface WeatherWidgetProps {
    weather: WeatherInterface;
}

export default function WeatherWidget({ weather }: WeatherWidgetProps) {
    const [isExpanded, setIsExpanded] = useState(false);

    // Safe access to weather data with fallbacks
    const weatherData = weather?.weather?.[0];
    const mainData = weather?.main;
    const windData = weather?.wind;
    
    // If no weather object at all
    if (!weather) {
        return (
            <div className="bg-card/95 backdrop-blur-sm border border-border shadow-lg rounded-lg transition-all duration-300 hover:shadow-xl flex items-center gap-2 px-3 py-2">
                <Cloud className="size-8 text-muted-foreground" />
                <div className="flex flex-col min-w-0">
                    <span className="text-lg font-semibold text-foreground leading-none">
                        --°C
                    </span>
                    <span className="text-xs text-muted-foreground">
                        No weather data
                    </span>
                </div>
            </div>
        );
    }
    
    // If essential weather data is missing, show a fallback
    if (!weatherData || !mainData) {
        return (
            <div className="bg-card/95 backdrop-blur-sm border border-border shadow-lg rounded-lg transition-all duration-300 hover:shadow-xl flex items-center gap-2 px-3 py-2">
                <Cloud className="size-8 text-muted-foreground" />
                <div className="flex flex-col min-w-0">
                    <span className="text-lg font-semibold text-foreground leading-none">
                        --°C
                    </span>
                    <span className="text-xs text-muted-foreground">
                        Invalid weather structure
                    </span>
                </div>
            </div>
        );
    }

    return (
        <div 
            className="relative"
            onMouseEnter={() => setIsExpanded(true)}
            onMouseLeave={() => setIsExpanded(false)}
        >
            {/* Compact view - always visible */}
            <div className="bg-card/95 backdrop-blur-sm border border-border shadow-lg rounded-lg transition-all duration-300 hover:shadow-xl flex items-center gap-2 px-3 py-2">
                <img 
                    src={`https://openweathermap.org/img/wn/${weatherData.icon}.png`} 
                    alt={weatherData.description || 'Weather'}
                    className="size-8 drop-shadow-sm flex-shrink-0"
                />
                <div className="flex flex-col min-w-0">
                    <span className="text-lg font-semibold text-foreground leading-none">
                        {Math.floor(mainData.temp)}°C
                    </span>
                    <span className="text-xs text-muted-foreground capitalize truncate">
                        {weatherData.description}
                    </span>
                </div>
            </div>
            
            {/* Expanded view - shows on hover */}
            <div className={`absolute top-0 right-0 w-80 bg-card/98 backdrop-blur-md border border-border rounded-lg shadow-xl transition-all duration-300 transform z-10 ${
                isExpanded 
                    ? 'translate-y-0 opacity-100 pointer-events-auto' 
                    : '-translate-y-full opacity-0 pointer-events-none'
            }`}>
                <div className="p-4 space-y-3">
                    {/* Main weather info */}
                    <div className="flex items-center gap-3">
                        <img 
                            src={`https://openweathermap.org/img/wn/${weatherData.icon}.png`} 
                            alt={weatherData.description || 'Weather'}
                            className="size-12 drop-shadow-sm flex-shrink-0"
                        />
                        <div className="flex flex-col">
                            <div className="flex items-center gap-2">
                                <span className="text-2xl font-semibold text-foreground leading-none">
                                    {Math.floor(mainData.temp)}°C
                                </span>
                            </div>
                            <span className="text-sm text-muted-foreground capitalize">
                                {weatherData.description}
                            </span>
                        </div>
                    </div>
                    
                    {/* Additional weather data */}
                    <div className="grid grid-cols-2 gap-2 pt-2 border-t border-border/50">
                        {mainData.feels_like && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Thermometer className="size-3 text-primary" />
                                <span>Feels {Math.floor(mainData.feels_like)}°</span>
                            </div>
                        )}
                        
                        {mainData.humidity && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Droplets className="size-3 text-blue-500" />
                                <span>{mainData.humidity}% humidity</span>
                            </div>
                        )}
                        
                        {windData?.speed && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Wind className="size-3 text-green-500" />
                                <span>{Math.round(windData.speed)} m/s</span>
                            </div>
                        )}
                        
                        {windData?.deg && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <ArrowUp className="size-3 text-gray-500" style={{ transform: `rotate(${windData.deg}deg)` }} />
                                <span>{windData.deg}°</span>
                            </div>
                        )}
                        
                        {mainData.pressure && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Gauge className="size-3 text-purple-500" />
                                <span>{mainData.pressure} hPa</span>
                            </div>
                        )}
                        
                        {weather.visibility && (
                            <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                <Eye className="size-3 text-orange-500" />
                                <span>{(weather.visibility / 1000).toFixed(1)} km</span>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
