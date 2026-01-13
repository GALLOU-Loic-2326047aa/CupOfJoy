<section class="company-info-home-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-8 offset-lg-2">
                <div class="company-info-card">
                    <div class="card-header">
                        <h2>Configurez votre solution café sur mesure</h2>
                        <p class="subtitle">Répondez à quelques questions pour commencer</p>
                    </div>

                    <div class="card-body">
                        {if isset($form_errors) && $form_errors}
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    {foreach $form_errors as $error}
                                        <li>{$error}</li>
                                    {/foreach}
                                </ul>
                            </div>
                        {/if}

                        <form action="{$urls.pages.index}" method="post" class="company-info-form" id="companyInfoForm">
                            <div class="questions-grid">
                                {foreach from=$mainQuestions item=question}
                                    <div class="form-group">
                                        <label for="{$question.name}" class="form-label">
                                            {$question.label} <span class="required">*</span>
                                        </label>
                                        <select name="{$question.name}" id="{$question.name}" class="form-control custom-select" required>
                                            <option value="">-- Sélectionnez --</option>
                                            {foreach from=$question.option item=option}
                                                <option value="{$option|escape:'html':'UTF-8'}">
                                                    {$option|escape:'html':'UTF-8'}
                                                </option>
                                            {/foreach}
                                        </select>
                                    </div>
                                {/foreach}
                            </div>

                            {if $dropdowns}
                                <div class="additional-questions">
                                    <h3>Informations complémentaires</h3>
                                    <div class="questions-grid">
                                        {foreach from=$dropdowns item=dropdown}
                                            <div class="form-group">
                                                <label for="{$dropdown.name}" class="form-label">
                                                    {$dropdown.label|escape:'html':'UTF-8'}
                                                </label>
                                                <select name="{$dropdown.name}" id="{$dropdown.name}" class="form-control custom-select" required>
                                                    <option value="">-- Sélectionnez --</option>
                                                    {if isset($dropdown.default) && $dropdown.default}
                                                        <option value="{$dropdown.default|escape:'html':'utf-8'}" selected>
                                                            {$dropdown.default|escape:'html':'UTF-8'}
                                                        </option>
                                                    {/if}
                                                    {foreach from=$dropdown.options item=option}
                                                        {if $option !== $dropdown.default}
                                                            <option value="{$option|escape:'html':'UTF-8'}">
                                                                {$option|escape:'html':'UTF-8'}
                                                            </option>
                                                        {/if}
                                                    {/foreach}
                                                </select>
                                            </div>
                                        {/foreach}
                                    </div>
                                </div>
                            {/if}

                            <div class="form-actions">
                                <button type="submit" name="submitCompanyInfo" class="btn btn-primary btn-lg">
                                    <span>Commencer ma sélection</span>
                                    <i class="material-icons">arrow_forward</i>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>