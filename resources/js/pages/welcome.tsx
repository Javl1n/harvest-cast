import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard, login, register } from '@/routes';
import { type SharedData } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { usePage } from '@inertiajs/react';
import { ArrowRight, Cloud, Droplets, Leaf, LineChart, MapPin, Sprout, TrendingUp } from 'lucide-react';

export default function Welcome() {
    const { auth } = usePage<SharedData>().props;

    const features = [
        {
            icon: Cloud,
            title: 'Weather Monitoring',
            description: 'Real-time weather data and forecasts to help you make informed decisions about your crops.',
        },
        {
            icon: Droplets,
            title: 'Soil Sensors',
            description: 'Monitor soil moisture, temperature, and other critical parameters across your farm.',
        },
        {
            icon: LineChart,
            title: 'Yield Forecasting',
            description: 'Predict harvest yields using AI-powered analytics and historical data.',
        },
        {
            icon: TrendingUp,
            title: 'Price Forecasting',
            description: 'Stay ahead of market trends with commodity price predictions.',
        },
        {
            icon: Leaf,
            title: 'Crop Recommendations',
            description: 'Get personalized suggestions for optimal crop selection and care.',
        },
        {
            icon: MapPin,
            title: 'Farm Mapping',
            description: 'Visualize your farm layout with interactive maps and sensor locations.',
        },
    ];

    return (
        <>
            <Head title="Welcome to Harvest Cast">
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
            </Head>

            <div className="min-h-screen bg-background">
                <header className="border-b border-border bg-card/50 backdrop-blur-sm sticky top-0 z-50">
                    <div className="container mx-auto flex h-16 items-center justify-between px-4">
                        <Link href="/" className="flex items-center gap-3">
                            <AppLogoIcon className="size-8 text-primary" />
                            <span className="text-xl font-semibold text-foreground">Harvest Cast</span>
                        </Link>

                        <nav className="flex items-center gap-3">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={dashboard()}>
                                        Dashboard
                                        <ArrowRight className="size-4" />
                                    </Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="ghost" asChild>
                                        <Link href={login()}>Log in</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={register()}>Get Started</Link>
                                    </Button>
                                </>
                            )}
                        </nav>
                    </div>
                </header>

                <main>
                    <section className="container mx-auto px-4 py-20 lg:py-32">
                        <div className="mx-auto max-w-3xl text-center">
                            <div className="mb-8 inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-2 text-sm font-medium text-primary">
                                <Sprout className="size-4" />
                                Smart Agriculture Platform
                            </div>

                            <h1 className="mb-6 text-4xl font-bold tracking-tight text-foreground sm:text-5xl lg:text-6xl">
                                Optimize Your Harvest with{' '}
                                <span className="text-primary">Data-Driven Insights</span>
                            </h1>

                            <p className="mb-10 text-lg text-muted-foreground sm:text-xl">
                                Monitor your crops, track weather conditions, and predict yields with our comprehensive
                                agricultural management platform. Make smarter decisions for better harvests.
                            </p>

                            <div className="flex flex-col items-center justify-center gap-4 sm:flex-row">
                                {auth.user ? (
                                    <Button size="lg" asChild>
                                        <Link href={dashboard()}>
                                            Go to Dashboard
                                            <ArrowRight className="size-5" />
                                        </Link>
                                    </Button>
                                ) : (
                                    <>
                                        <Button size="lg" asChild>
                                            <Link href={register()}>
                                                Get Started Free
                                                <ArrowRight className="size-5" />
                                            </Link>
                                        </Button>
                                        <Button size="lg" variant="outline" asChild>
                                            <Link href={login()}>Sign In</Link>
                                        </Button>
                                    </>
                                )}
                            </div>
                        </div>
                    </section>

                    <section className="border-t border-border bg-muted/30 py-20">
                        <div className="container mx-auto px-4">
                            <div className="mb-12 text-center">
                                <h2 className="mb-4 text-3xl font-bold text-foreground sm:text-4xl">
                                    Everything You Need to Manage Your Farm
                                </h2>
                                <p className="mx-auto max-w-2xl text-lg text-muted-foreground">
                                    Comprehensive tools and insights to help you grow better crops and maximize your yield.
                                </p>
                            </div>

                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {features.map((feature, index) => (
                                    <Card key={index} className="transition-shadow hover:shadow-lg">
                                        <CardHeader>
                                            <div className="mb-4 flex size-12 items-center justify-center rounded-lg bg-primary/10">
                                                <feature.icon className="size-6 text-primary" />
                                            </div>
                                            <CardTitle>{feature.title}</CardTitle>
                                            <CardDescription>{feature.description}</CardDescription>
                                        </CardHeader>
                                    </Card>
                                ))}
                            </div>
                        </div>
                    </section>

                    <section className="border-t border-border py-20">
                        <div className="container mx-auto px-4">
                            <div className="mx-auto max-w-3xl">
                                <Card className="bg-gradient-to-br from-primary/5 to-primary/10 border-primary/20">
                                    <CardHeader className="text-center">
                                        <CardTitle className="text-3xl">Ready to Get Started?</CardTitle>
                                        <CardDescription className="text-base">
                                            Join farmers who are already using Harvest Cast to optimize their operations.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="flex justify-center">
                                        {auth.user ? (
                                            <Button size="lg" asChild>
                                                <Link href={dashboard()}>
                                                    Open Dashboard
                                                    <ArrowRight className="size-5" />
                                                </Link>
                                            </Button>
                                        ) : (
                                            <Button size="lg" asChild>
                                                <Link href={register()}>
                                                    Create Your Account
                                                    <ArrowRight className="size-5" />
                                                </Link>
                                            </Button>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </div>
                    </section>
                </main>

                <footer className="border-t border-border bg-card py-12">
                    <div className="container mx-auto px-4">
                        <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div className="flex items-center gap-3">
                                <AppLogoIcon className="size-6 text-primary" />
                                <span className="text-sm font-medium text-muted-foreground">
                                    Harvest Cast &copy; {new Date().getFullYear()}
                                </span>
                            </div>

                            <div className="flex items-center gap-6 text-sm text-muted-foreground">
                                <a
                                    href="https://laravel.com/docs"
                                    target="_blank"
                                    className="transition-colors hover:text-foreground"
                                >
                                    Documentation
                                </a>
                                <a
                                    href="https://github.com"
                                    target="_blank"
                                    className="transition-colors hover:text-foreground"
                                >
                                    GitHub
                                </a>
                            </div>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
