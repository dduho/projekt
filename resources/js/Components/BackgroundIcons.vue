<template>
    <div class="fixed inset-0 pointer-events-none z-0 overflow-hidden">
        <!-- Icônes Mobile Money / Finances -->
        <Smartphone :size="120" class="background-icon float-slow" style="top: 10%; left: 5%;" />
        <Coins :size="80" class="background-icon float-medium" style="top: 25%; right: 8%;" />
        <CreditCard :size="100" class="background-icon pulse-subtle" style="bottom: 15%; left: 10%;" />
        <Wallet :size="90" class="background-icon float-fast" style="top: 60%; right: 15%;" />
        <Banknote :size="110" class="background-icon float-slow" style="bottom: 30%; right: 25%;" />
        
        <!-- Icônes Projets / Gestion -->
        <FolderKanban :size="95" class="background-icon float-medium" style="top: 15%; right: 20%;" />
        <Target :size="85" class="background-icon pulse-subtle" style="top: 45%; left: 8%;" />
        <TrendingUp :size="105" class="background-icon float-fast" style="top: 70%; left: 20%;" />
        <BarChart3 :size="90" class="background-icon float-slow" style="bottom: 40%; left: 35%;" />
        <PieChart :size="100" class="background-icon float-medium" style="top: 35%; right: 35%;" />
        
        <!-- Icônes Tech / Digital -->
        <Zap :size="75" class="background-icon pulse-subtle" style="top: 80%; right: 30%;" />
        <Network :size="95" class="background-icon float-fast" style="bottom: 25%; right: 10%;" />
        <Rocket :size="85" class="background-icon float-slow" style="top: 50%; left: 40%;" />
        <CircuitBoard :size="110" class="background-icon float-medium" style="bottom: 10%; left: 45%;" />
        
        <!-- Icônes Business -->
        <Building2 :size="100" class="background-icon pulse-subtle" style="top: 20%; left: 30%;" />
        <Briefcase :size="80" class="background-icon float-slow" style="bottom: 50%; right: 40%;" />
        <LineChart :size="90" class="background-icon float-fast" style="top: 40%; left: 60%;" />
        <DollarSign :size="120" class="background-icon float-medium" style="bottom: 20%; left: 60%;" />
        
        <!-- Icônes Petites discrètes -->
        <Percent :size="60" class="background-icon pulse-subtle" style="top: 55%; right: 5%;" />
        <TrendingDown :size="65" class="background-icon float-slow opacity-20" style="top: 85%; left: 15%;" />
        <ArrowUpRight :size="55" class="background-icon float-fast opacity-20" style="bottom: 45%; left: 25%;" />
        <Activity :size="70" class="background-icon float-medium opacity-20" style="top: 30%; left: 50%;" />
    </div>
</template>

<script setup>
import {
    Smartphone,
    Coins,
    CreditCard,
    Wallet,
    Banknote,
    FolderKanban,
    Target,
    TrendingUp,
    BarChart3,
    PieChart,
    Zap,
    Network,
    Rocket,
    CircuitBoard,
    Building2,
    Briefcase,
    LineChart,
    DollarSign,
    Percent,
    TrendingDown,
    ArrowUpRight,
    Activity
} from 'lucide-vue-next';
</script>

<style scoped>
/* Opacités supplémentaires pour certaines icônes */
.opacity-20 {
    opacity: 0.02 !important;
}

/* Animation delay pour un effet plus naturel */
.background-icon:nth-child(2n) {
    animation-delay: -5s;
}

.background-icon:nth-child(3n) {
    animation-delay: -10s;
}

.background-icon:nth-child(5n) {
    animation-delay: -15s;
}
</style>
